<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UserNotEnrolledException;
use App\Model;
use App\Services\Logger;
use Nette;
use Nette\Security;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;


/**
 * Autenticator
 * Last change 14.07.2022
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2022 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class PVAuthenticator implements Security\Authenticator
{
  private $passwords;
  private $request;

  /** @var Model\PV_User_main */
  private $pv_user;

  const NAME = 'audit';

  public function __construct(
    Model\PV_User_main $pv_user,
    Security\Passwords $passwords,
    Nette\Http\Request $request
  ) {
    $this->pv_user = $pv_user;
    $this->passwords = $passwords;
    $this->request = $request;
  }

  private function badPasswordAction($id, $badPwdCount, $lockoutTime, $ip, $browser)
  {
    $this->pv_user->save($id, [
      'state_id'/*'id_user_state'*/ => 91,
      'bad_pwds_count' => $badPwdCount,
      'locked_out_until' => $lockoutTime,
      'last_error_time' => new DateTime(),
      'last_error_ip' => $ip,
      'last_error_browser' => $browser,
    ]);
  }

  private function loginOkAction($userData, $ip, $browser)
  {
    $this->pv_user->save($userData->id, [
      'state_id'/*'id_user_state'*/ => 10,
      'bad_pwds_count' => 0,
      'cur_login_time' => new DateTime(),
      'cur_login_ip' => $ip,
      'cur_login_browser' => $browser,
      'prev_login_time' => $userData->cur_login_time,
      'prev_login_ip' => $userData->cur_login_ip,
      'prev_login_browser' => $userData->cur_login_browser
    ]);
  }

  /**
   * Performs an authentication.
   * @param string $email
   * @param string $password
   * @return Nette\Security\SimpleIdentity
   * @throws Nette\Security\AuthenticationException 
   *  IDENTITY_NOT_FOUND = 1
   *  INVALID_CREDENTIAL = 2
   *  FAILURE = 3
   *  NOT_APPROVED = 4 
   *  Pridané:
   *  locked by the system administrator = 5
   *  temporarily locked = 6*/
  public function authenticate(string $email, string $password): Security\SimpleIdentity
  {
    $ip = $this->request->getRemoteAddress();
    $ua = $this->request->getHeader('User-Agent') . ' / ' . $this->request->getHeader('Accept-Language');

    $userData = $this->pv_user->getUserBy(['email' => $email]);
    //dumpe($userData);
    if (!$userData) {
      Logger::log(self::NAME, Logger::ERROR, "[{$ip}] Login: nenajdeny uzivatel s emailem: {$email}, '{$ua}'");
      throw new Security\AuthenticationException('', 1);
    }
    if ($userData->state_id/*->id_user_state*/ == 1) {
      Logger::log(self::NAME, Logger::ERROR, "[{$ip}] Login: {$email} state=1 ");
      throw new UserNotEnrolledException('');
    } else if ($userData->state_id/*->id_user_state*/ == 90) {
      Logger::log(self::NAME, Logger::ERROR, "[{$ip}] Login: {$email} state=90, '{$ua}'");
      throw new Security\AuthenticationException('', 5);
    } else if ($userData->state_id/*->id_user_state*/ == 91) {
      $lockoutTime = (DateTime::from($userData->locked_out_until))->getTimestamp();
      if ($lockoutTime > time()) {
        $rest = $lockoutTime - time();
        Logger::log(self::NAME, Logger::ERROR, "[{$ip}] Login: {$email} state=91 for {$rest} sec; '{$ua}'");
        throw new Security\AuthenticationException((string)$rest, 6);
      }
    } else if ($userData->state_id/*->id_user_state*/ == 10) {
      // OK, korektny stav
    }

    if (!$this->passwords->verify($password, $userData->phash)) {
      $badPwdCount = $userData->bad_pwds_count + 1;
      Logger::log(self::NAME, Logger::ERROR, "[{$ip}] Login: chybne heslo pre {$email}, badPwdCount={$badPwdCount}, '{$ua}'");

      $delay = pow(2, $badPwdCount);
      $lockoutTime = (new DateTime())->setTimestamp(time() + $delay);
      $this->badPasswordAction($userData->id, $badPwdCount, $lockoutTime, $ip, $ua);

      throw new Security\AuthenticationException('', 2);
    }

    // pokud heslo potrebuje rehash, rehashnout
    if ($this->passwords->needsRehash($userData->phash)) {
      $this->pv_user->save($userData->id, ['phash' => $this->passwords->hash($password)]);
    }

    $this->loginOkAction($userData, $this->request->getRemoteAddress(), $ua);

    Logger::log(self::NAME, Logger::INFO, "[{$ip}] Login: prihlaseny {$email} v roli '{$userData->role/*user_roles->name*/}', '{$ua}'");

    //$role = $userData->user_roles->role;
    $role = Strings::split($userData->role, '~,\s*~');
    return new Security\SimpleIdentity($userData->id, $role, ['email' => $userData->email, 'prefix' => $userData->prefix/*, 'id_user_roles' => $userData->id_user_roles*/]);
  }
}
