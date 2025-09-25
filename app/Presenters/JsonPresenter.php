<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette;
use Nette\Utils\DateTime;

/**
 * Presenter pre prácu s JSON
 * Posledna zmena 25.09.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
final class JsonPresenter extends BasePresenter
{
	use Nette\SmartObject;

	// Database tables
	/** @var Model\PV_Devices @inject */
	public $devices;

	/** @var Services\InventoryDataSource */
	private $datasource;

	public function __construct(Services\InventoryDataSource $datasource)
	{
		$this->datasource = $datasource;
	}
	/**
	 * Úvodná testovacia akcia - popis API
	 */
	public function actionDefault(): void
	{
		// Ukážka použitia API
		$bu = $this->link("//Homepage:");
		$this->sendJson(['status' => '200', 'message' => 'JSON API', 'data' => [
			'baseUrl'	=> $bu,
			'data_link' => $bu . 'json/data/{id}/{token}/',
			'meteo_link' => $bu . 'json/meteo/{id}/{token}/?temp={temp_sensor_name}&rain={rain_sensor_name}',
		]]);
	}
	/**
	 * Overi platnosť id a tokenu
	 * @param int $id Id zariadenia
	 * @param string $token Token zariadenia
	 * @return array Informácie o zariadení
	 * @throws \Exception */
	private function _testIdToken(int $id = 0, string $token = ""): array
	{
		if ($id == 0 || $token == "") {
			throw new \Exception("Chybný formát požiadavky.", 400);
		}

		$device = $this->devices->getDevice($id, true, true);
		if (!$device) {
			throw new \Exception("Zariadenie s id:{$id} sa nenašlo", 404);
		}

		if (!$token || ($device['data']['json_token'] !== $token)) {
			throw new \Exception("Token nesúhlasí.", 403);
		}
		return $device;
	}


	// json/data/{token}/2/
	public function renderData(int $id = 0, string $token = ""):void
	{
		try {
			$device = $this->_testIdToken($id, $token);
		} catch (\Exception $e) {
			$this->sendJson(['status' => $e->getCode(), 'message' => $e->getMessage()]);
			return;
		}
		
		// Pridanie stavu jednotlivých senzorov
		foreach ($device['data']['sensors'] as $key => $sensor) {
			$status = 'not_yet_connected';
			if ($sensor['last_data_time']) {
				$utime = (DateTime::from($sensor['last_data_time']))->getTimestamp();
				if (time() - $utime > $sensor['msg_rate']) {
					$status = 'too_old_msg';
				} else {
					$status = 'OK';
				}
			}
			$device['data']['sensors'][$key]['status'] = $status;
		}
		$data = [
			'device_id' => $id,
			'device_name' => $device['data']['name'],
			'device_desc' => $device['data']['desc'],
			'sensors' => $device['data']['sensors'],
		];

		$response = $this->getHttpResponse();
		$response->setHeader('Cache-Control', 'no-cache');
		$response->setExpiration('1 sec');

		$this->sendJson(['status' => '200', 'message' => 'OK', 'data' => $data]);
	}

	// json/meteo/aaabbb/2/?temp=bd358d05&rain=rain
	public function renderMeteo(int $id = 0, string $token = "", string $temp = "", string $rain = ""): void
	{
		try {
			$device = $this->_testIdToken($id, $token);
		} catch (\Exception $e) {
			$this->sendJson(['status' => $e->getCode(), 'message' => $e->getMessage()]);
			return;
		}
		$tempId = -1;
		$rainId = -1;
		$sensors = $device['data']['sensors'];
		foreach ($sensors as $sensor) {
			if ($sensor['name'] === $temp) {
				$tempId = $sensor['id'];
				$tempCurrent = $sensor['last_out_value'];
				$lastDataTime = $sensor['last_data_time'];
				$maxDataTime = $sensor['msg_rate'];
			}
			if ($sensor['name'] === $rain) {
				$rainId = $sensor['id'];
			}
		}

		if (isset($lastDataTime)) {
			if ((time() - ($lastDataTime->getTimestamp())) >  $maxDataTime) {
				$dataValid = "N";
			} else {
				$dataValid = "Y";
			}
		} else {
			$dataValid = "N";
		}


		$response = $this->getHttpResponse();
		$response->setHeader('Cache-Control', 'no-cache');
		$response->setExpiration('1 sec');


		/*if ($tempId == -1 || $rainId == -1) {
			$data = [
				'error' => 'Nenalezen senzor daneho jmena.'
			];
			$this->sendJson($data);
			$this->terminate();
			return;
		}*/
		$data = [
			'error_temp' => $tempId == -1 ? 'Nenašiel som senzor teploty.' : null,
			'error_rain' => $rainId == -1 ? 'Nenašiel som senzor zrážok.' : null,
		];

		// Dáta za posledný týždeň, včerajšok, dnešok, noc a aktuálna teplota
		$date = (new DateTime())->modify('-7 day')->format('Y-m-d');
		$rainTyden = $rainId != -1 ? $this->datasource->meteoGetWeekData($rainId, $date) : ['sum' => null];
		$tempTyden = $tempId != -1 ? $this->datasource->meteoGetWeekData($tempId, $date) : ['min' => null, 'max' => null];
		$date = (new DateTime())->modify('-1 day')->format('Y-m-d');
		$rainVcera = $rainId != -1 ? $this->datasource->meteoGetDayData($rainId, $date) : ['sum' => null];
		$tempVcera = $tempId != -1 ? $this->datasource->meteoGetDayData($tempId, $date) : ['min' => null, 'max' => null];
		$date2 = (new DateTime())->format('Y-m-d');
		$rainNoc = $rainId != -1 ? $this->datasource->meteoGetNightData($rainId, $date, $date2) : ['sum' => null];
		$tempNoc = $tempId != -1 ? $this->datasource->meteoGetNightData($tempId, $date, $date2) : ['min' => null, 'max' => null];
		$rainDnes = $rainId != -1 ? $this->datasource->meteoGetDayData($rainId, $date2) : ['sum' => null];
		$tempDnes = $tempId != -1 ? $this->datasource->meteoGetDayData($tempId, $date2) : ['min' => null, 'max' => null];

		$tyden = [
			'rain' => $rainTyden['sum'],
			'temp_min' => $tempTyden['min'],
			'temp_max' => $tempTyden['max'],
		];
		$vcera = [
			'rain' => $rainVcera['sum'],
			'temp_min' => $tempVcera['min'],
			'temp_max' => $tempVcera['max'],
		];
		$noc = [
			'rain' => $rainNoc['sum'],
			'temp_min' => $tempNoc['min'],
			'temp_max' => $tempNoc['max'],
		];
		$dnes = [
			'rain' => $rainDnes['sum'],
			'temp_min' => $tempDnes['min'],
			'temp_max' => $tempDnes['max'],
		];
		$nyni = [
			'temp' => $tempCurrent,
			'valid' => $dataValid,
			'timestamp' => $lastDataTime
		];
		$data = array_merge($data, [
			'status' => '200',
			'message' => 'OK',
			'a_week' => $tyden,
			'yesterday' => $vcera,
			'night' => $noc,
			'today' => $dnes,
			'now' => $nyni,
			'temp_now' => $tempCurrent,
		]);

		$this->sendJson($data);
	}
}
