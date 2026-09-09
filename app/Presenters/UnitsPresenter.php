<?php

namespace App\Presenters;

use App\Model;

/**
 * Prezenter pre pristup k api jednotiek.
 * Posledna zmena(last change): 08.08.2026
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 */
class UnitsPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\Units @inject */
	public $units;

	public function actionDefault(int $id = 0): void
	{
		$this->sendJson($this->units->getUnits((bool)$id));
	}

	public function actionSave(int $id = 0): void
	{
		if (!$this->user->isLoggedIn()) {
			$this->sendJson(["status" => 401, "message" => "Nie ste prihlásený!"]);
		} else if (!$this->user->isInRole('admin')) {
			$this->sendJson(["status" => 403, "message" => "Nemáte oprávnenie na túto akciu!"]);
		} else {
			$_post = json_decode(file_get_contents("php://input"), true);
			if (empty($_post)) {
				$this->sendJson(["status" => 400, "message" => "Chýbajúce dáta pre aktualizáciu jednotky!"]);
			} else {
				if ($id > 0 && !$this->units->find($id)) {
					$this->sendJson(["status" => 404, "message" => "Jednotka s id $id neexistuje!"]);
				}	else {
					$updateResult = $this->units->save($id, $_post);
					if ($updateResult) {
						$this->sendJson([
							"message" => "Jednotka s id $id bola úspešne aktualizovaná.", 
							...$this->units->getUnits(true)
						]);
					} else {
						$this->sendJson(["status" => 500, "message" => "Nepodarilo sa aktualizovať jednotku s id $id."]);
					}
				}
			}
		}
	}

	public function actionDelete(int $id): void
	{
		if (!$this->user->isLoggedIn()) {
			$this->sendJson(["status" => 401, "message" => "Nie ste prihlásený!"]);
		} else if (!$this->user->isInRole('admin')) {
			$this->sendJson(["status" => 403, "message" => "Nemáte oprávnenie na túto akciu!"]);
		} else {
			if ($this->units->zmaz($id)) {
				$this->sendJson([
							"message" => "Jednotka s id $id bola úspešne vymazaná.",
							...$this->units->getUnits(true),
				]);
			} else {
				$this->sendJson(["status" => 500, "message" => "Nepodarilo sa vymazať jednotku s id $id."]);
			}
		}
	}
}
