<?php

namespace App\Presenters;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 20.11.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.0
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
}
