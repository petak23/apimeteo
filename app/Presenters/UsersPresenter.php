<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\Validators;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 20.12.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.2
 * @help 1.) https://forum.nette.org/cs/28370-data-z-post-request-body-reactjs-appka-se-po-ceste-do-php-ztrati
 * @help 2.) https://www.php.net/manual/en/function.checkdnsrr.php#48157
 */
class UsersPresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->sendJson($this->user_main->getUsers(true));
	}

	/**
	 * Vráti konkrétneho užívateľa. Ak je id = 0 vráti aktuálne prihláseného užívateľa
	 */
	public function actionUser(int $id = 0): void
	{
		$this->sendJson(
			$this->user_main->getUser(
				($id == 0) ? $this->user->getId() : $id,
				$this->user,
				$this->template->baseUrl,
				true
			)
		);
	}

	public function actionLogIn(): void
	{
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)

		try {
			if (!Validators::isEmail($post['email'])) { // Kontrola, či bol zadaný email v správnom tvare
				throw new Nette\InvalidArgumentException;
			}
			if(!checkdnsrr(array_pop(explode("@",$email)),"MX")){ // @help 2.)
        throw new Nette\InvalidArgumentException;						// Kontrola, či daná doména existuje
			}
			
			$this->user->login($post['email'], $post['password']);
			$this->sendJson(
				$this->user_main->getUser(
					$this->user->getId(),
					$this->user,
					$this->template->baseUrl,
					true
				)
			);
		} catch (Nette\Security\AuthenticationException $e) {
			$this->sendJson(['status'=>500, 'error'=>'Uživateľské meno alebo heslo je nesprávne!!!']);
		} catch (Nette\InvalidArgumentException $e) {
			$this->sendJson(['status'=>500, 'error'=>'Zadajte email v správnom tvare!!!']);
		}
		
	}
}
