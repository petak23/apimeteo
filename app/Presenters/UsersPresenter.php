<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\Validators;
//use Nette\Utils\Random;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 29.10.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.4
 * @help 1.) https://forum.nette.org/cs/28370-data-z-post-request-body-reactjs-appka-se-po-ceste-do-php-ztrati
 * @help 2.) https://www.php.net/manual/en/function.checkdnsrr.php#48157
 */
class UsersPresenter extends BasePresenter
{
	/**
	 * Vráti zoznam všetkých užívateľov */
	public function actionDefault(): void
	{
		$this->sendJson($this->user_main->getUsers(true));
	}

	private function _sendUserJson(
		int $status = 400,
		string $message = 'Error user action',
		mixed $user_data = null,
		string|null $token = null,
		mixed $permission = null
	): void
	{
		$this->sendJson([
				'status' => $status, 
				'message' => $message,
				'user' => $user_data,
				'token' => $token,
				'permission' => $permission,
			]);
		return;
	}

	/**
	 * Vráti(cez sendJson) konkrétneho užívateľa. 
	 * Ak je id = 0 vráti aktuálne prihláseného užívateľa, inak 
	 * vráti užívateľa so zadaným id, ale len ak prihlásený užívateľ je admin
	 * Ak užívateľ nie je prihlásený, tak vráti info
	 */
	public function actionUser(int $id = 0): void
	{
		$usr = $this->user;
		if (!$usr->isLoggedIn()) {
			$this->_sendUserJson(401, 'Užívateľ nie je prihlásený!');
		}
		$_tmp = $this->user_main->getUser(
				($id != 0 && $usr->getIdentity()->id_user_roles > 3) ? $id : $usr->getId(),
				$usr->getId(),
				$this->template->baseUrl,
				true
		);
		unset($_tmp['data']['new_password_key'], 
					$_tmp['data']['new_password_requested'], 
					$_tmp['data']['comm_id'],
					$_tmp['data']['self_enroll'],
					$_tmp['data']['self_enroll_code'],
					$_tmp['data']['self_enroll_error_count'],
					$_tmp['data']['role'], // TODO @deprecadet
				);
		$permission = $this->user_permission->getAllowedPermission($usr->getIdentity()->id_user_roles, true);
		$this->_sendUserJson($_tmp['status'], "", $_tmp['data'],
											$usr->getIdentity()->comm_id, 
											$permission);
	}

	public function actionLogIn(): void
	{
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)
		
		try {
			if (!Validators::isEmail($_post['email'])) { // Kontrola, či bol zadaný email v správnom tvare
				throw new Nette\InvalidArgumentException("Zadajte email v správnom tvarey.");
			}
			if (!Validators::is($_post['password'], "string:6..")) { // Kontrola dĺžky hesla
				throw new Nette\InvalidArgumentException("Heslo musí mať minimálne 6 znakov.");
			}
			
			$this->user->login($_post['email'], $_post['password']);
			
			$this->actionUser();

		} catch (Nette\Security\AuthenticationException $e) {
			$this->_sendUserJson(500, 'Uživateľské meno alebo heslo je nesprávne!!!');
		} catch (Nette\InvalidArgumentException $e) {
			$this->_sendUserJson(500, $e->getMessage());
		}
		
	}

	public function actionLogOut() : void 
	{
		$this->user->logout(true);
		$this->_sendUserJson(200, "Užívateľ bol odhlásený.");	
	}

	public function actionSave(int $id): void
	{
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)
		try {
			if (!Validators::isEmail($_post['email'])) { // Kontrola, či bol zadaný email v správnom tvare
				throw new Nette\InvalidArgumentException;
			}

			$this->user_main->save($id, $_post);
			$this->actionUser($id);
		} catch (Nette\InvalidArgumentException $e) {
			$this->_sendUserJson(500, 'Zadajte email v správnom tvare!!!');
		}
	}

	/** 
	 * Zmení heslo užívateľa
	 * Admin môže meniť heslo ktorémukoľvek užívateľovi, bežný užívateľ len svoje vlastné ale musí byť prihlásený a zadať aj staré heslo
	 * @param int $id Id užívateľa, ktorého heslo sa má zmeniť
	 */
	public function actionPasswordChange(int $id): void
	{
		// Kontrola prihlásenia
		if ($this->user->isLoggedIn() === false) {
			$this->_sendUserJson(401, 'Užívateľ nie je prihlásený!');
			return;
		}
		// Kontrola práv na zmenu hesla iného užívateľa
		if ($this->user->getIdentity()->id_user_roles < 4 && $this->user->getId() != $id) {
			$this->_sendUserJson(403, 'Nemáte dostatočné práva na zmenu hesla iného užívateľa!');
			return;
		}
		
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)
		
		try {
			$this->user_main->changePassword($id, $_post['old_password'], $_post['new_password']);
			$this->_sendUserJson(200, 'Heslo bolo zmenené.');
		} catch (Nette\InvalidArgumentException $e) {
			$this->_sendUserJson(500, $e->getMessage());
		}
	}
}