<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette\Http\Url;
use Nette\Utils\Strings;

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 31.07.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.4
 */
class DevicesPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\Devices @inject */
	public $devices;
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
				$device['data'] = array_merge($dd, [
					'jsonUrl'		=> $this->link('//:Json:data', ['token' => $dd['json_token'], 'id' => $dd['id']]),
					'jsonUrl2'	=> $this->link('//:Json:meteo', ['token' => $dd['json_token'], 'id' => $dd['id'], 'temp' => 'MENO_TEMP_SENZORU', 'rain' => 'MENO_RAIN_SENZORU']),
					'blobUrl'		=> $this->link('//:Gallery:show', ['token' => $dd['blob_token'], 'id' => $dd['id']]),
					'url'				=> /*$this->link('//:Ra:')*/ $this->template->baseUrl . '/ra',
					'name_no_prefix' => $name_no_prefix,
					'passphrase' => $this->config->decrypt($dd['passphrase'], $dd['name']),
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
		//dumpe($_post);
		$values = $_post;
		$values['name'] = $this->user->getIdentity()->prefix.":".$_post['name'];
		$values['user_id'] = $this->user->id;
		$values['passphrase'] = $this->config->encrypt( $_post['passphrase'], $_post['name'] );

		if( $id ) {
			// editace
			$device = $this->devices->getDevice( $id );
			//dumpe($device);
			if (!$device) {
				$out = ["status" => 404, "message" => "Zariadenie sa nenašlo"];
			} else if( $this->user->id != $device->attrs->user_id ) {
				// TODO Add Logger
				//Logger::log( 'audit', Logger::ERROR , 
				//	sprintf("Užívateľ #%s (%s) zkúsil editovať zariadenie patriace užívateľovi #%s", $this->user->id, $this->user->getIdentity()->email, $device->user_id));
				$this->user->logout(true);
				//$form->addError($this->texts->translate('device_form_not_aut'), "danger");
				$out = ["status" => 500, "message" => "K tomuto zariadeniu nemáte oprtávnený prístup!"];
			} else {
				
				$device->update( $values );
				$out = ["status" => 200, "message" => "Údaje zariadenia aktualizované."];
			}
		} else {
			// zalozeni
			$this->pv_devices->createDevice( $values );
			$out = ["status" => 200, "message" => "Zariadenie bolo vytvorené."];
		}
		$this->sendJson($out);
	}
}
