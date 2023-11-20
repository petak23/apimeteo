<?php

namespace App\ApiModule\Presenters;

use App\ApiModule\Model;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 29.09.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 */
class DevicesPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\Devices @inject */
	public $devices;
	/** @var Model\Measures @inject */
	public $measures;

	public function actionDefault(): void
	{
		$this->sendJson($this->devices->getDevicesUser($this->user->id, true));
	}

	public function actionDevice(int $id = 0): void
	{
		$this->sendJson($this->devices->getDevice($id, true, true));
	}

	/** Vráti zoznam senzorov pre dané zariadenie */
	public function actionSensors(int $id): void
	{
		$d = $this->devices->getDevice($id, true, true);
		$this->sendJson($d["sensors"]);
	} 

	public function actionMeasures(int $id): void
	{
		$this->sendJson($this->measures->getMeasures($id));
	}

	public function actionMeasureslast(int $id): void
	{
		$this->sendJson($this->measures->getLastMeasure($id));
	}
}
