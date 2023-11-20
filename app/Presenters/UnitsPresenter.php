<?php

namespace App\ApiModule\Presenters;

use App\ApiModule\Model;

/**
 * Prezenter pre pristup k api jednotiek.
 * Posledna zmena(last change): 13.09.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.0
 */
class UnitsPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\Units @inject */
	public $units;

	public function actionDefault(): void
	{
		$this->sendJson($this->units->getUnits());
	}
}
