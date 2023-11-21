<?php

namespace App\Presenters;

use Nette;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 21.11.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 * @help 1.) https://forum.nette.org/cs/28370-data-z-post-request-body-reactjs-appka-se-po-ceste-do-php-ztrati
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
			$this->sendJson(['error'=>'Uživateľské meno alebo heslo je nesprávne!!!']);
		}
		
	}
}
