<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette\Database;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 03.08.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.5
 */
class DevicesPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\PV_Devices @inject */
	public $devices;
	/** @var Model\PV_Sensors @inject */
	public $sensors;
	/** @var Model\Measures @inject */
	public $measures;

	/** @var Services\Config */
	public $config;

	public function __construct(Services\Config $config) {
		$this->config = $config;
	}

	public function actionDefault(): void
	{
		$this->sendJson($this->devices->getDevicesUser($this->user->id, true));
	}
	/**
	 * Vráti cez sendJson informácie o jednom zariadení
	 * @param int $id Id zariadenia */
	public function actionDevice(int $id = 0): void
	{
		if ($id > 0) {
			$device = $this->devices->getDevice($id, true, true);
			if ($device['status'] == 200) {
				$dd = $device['data'];
				$arr = Strings::split($dd['name'], '~:~');
				$name_no_prefix = $arr[1];
				$pp = $this->config->decrypt($dd['passphrase'], $name_no_prefix);
				if (!strlen($pp)) {
					$pp = $this->config->decrypt($dd['passphrase'], $dd['name']);
					if (!strlen($pp)) {
						$device = [
							'status' => 500,
							'message' => "Chybná passphrase, nie je možné dešifrovať údaje zariadenia."
						];
						$this->sendJson($device);
						return;
					}
				}
				$lastLoginTs = (DateTime::from($dd['last_login']))->getTimestamp();
				$lastTime = $lastLoginTs;

				foreach ($dd['sensors'] as $sensor) {
					if ($sensor['last_data_time']) {
						$utime = (DateTime::from($sensor['last_data_time']))->getTimestamp();
						if (!$lastTime || ($utime > $lastTime)) {
							$lastTime = $utime;
						}
					}
				}

				$device['data'] = array_merge($dd, [
					'jsonUrl'		=> $this->link('//:Json:data', ['token' => $dd['json_token'], 'id' => $dd['id']]),
					'jsonUrl2'	=> $this->link('//:Json:meteo', ['token' => $dd['json_token'], 'id' => $dd['id'], 'temp' => 'MENO_TEMP_SENZORU', 'rain' => 'MENO_RAIN_SENZORU']),
					'blobUrl'		=> $this->link('//:Gallery:show', ['token' => $dd['blob_token'], 'id' => $dd['id']]),
					'url'				=> /*$this->link('//:Ra:')*/ $this->template->baseUrl . '/ra',
					'name_no_prefix' => $name_no_prefix,
					'passphrase' => $pp,
					'last_data_time' => $lastTime ? DateTime::from($lastTime)->format('d.m.Y H:i:s') : null
				]);
			}
		} else {
			$device = [
				'status' => 404,
				'message' => "Invalid device Id..."
			];
		}
		$this->sendJson($device);
	}

	/** Vráti zoznam senzorov pre dané zariadenie */
	public function actionSensors(int $id): void
	{
		$d = $this->devices->getDevice($id, true, true);
		$this->sendJson($d["sensors"]);
	}

	public function actionSensor(int $id): void
	{
		$sensor = $this->sensors->getSensor($id, true);
		$this->sendJson($sensor);
	}

	public function actionMeasures(int $id): void
	{
		$this->sendJson($this->measures->getMeasures($id));
	}

	public function actionMeasureslast(int $id): void
	{
		$this->sendJson($this->measures->getLastMeasure($id));
	}

	public function actionEdit(int $id) : void {
		
		$_post = json_decode(file_get_contents("php://input"), true);
		
		if ($_post == null) {
			$out = ["status" => 404, "message" => "Nekorektné alebo chýbajúce data z formuláru"];
		} else {
			$values = $_post;
			$values['name'] = $this->user->getIdentity()->prefix.":".$_post['name'];
			$values['user_id'] = $this->user->id;
			$values['passphrase'] = $this->config->encrypt( $_post['passphrase'], $_post['name'] );
			//dumpe($values, $_post, $id);
			if( $id ) {
				// editace
				$device = $this->devices->getDevice( $id );
				//dumpe($device);
				if (!$device) {
					$out = ["status" => 404, "message" => "Zariadenie sa nenašlo"];
				} else if( $this->user->id != $device->attrs->user_id ) {
					Services\Logger::log( 'audit', Services\Logger::ERROR , 
						sprintf("Užívateľ #%s (%s) zkúsil editovať zariadenie patriace užívateľovi #%s", $this->user->id, $this->user->getIdentity()->email, $device->user_id));
					$this->user->logout(true);
					$out = ["status" => 500, "message" => "K tomuto zariadeniu nemáte oprávnený prístup!"];
				} else {
					
					$up = $device->attrs->update( $values );
					//$up = $this->devices->getDeviceSimple($id)->update( $values );
					$out = ["status" => 200, "message" => "Údaje zariadenia aktualizované."];
					//dumpe($out);
				}
			} else { // zalozeni
				try {
					$new_device = $this->devices->createDevice( $values , true );
					$out = ["status" => 200, "message" => "Zariadenie bolo vytvorené.", "device" => $new_device];
				} catch (Database\UniqueConstraintViolationException  $e) {
					$out = ["status" => 500, 
									"message" => "Chyba pri vytváraní zariadenia: 
															 s názvom '".$values["name"]."' už existuje. Prosím, zvoľte iný názov."
								 ];
				} catch (\Exception $e) {
					$out = ["status" => 500, 
									"message" => "Chyba pri vytváraní zariadenia: " . $e->getMessage()
								];
				}
			}
		}
		
		$this->sendJson($out);
	}
}
