<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Database\Table;
use Nette\Utils\DateTime;

use App\Exceptions\NoSessionException;
use App\Model;
use App\ApiModule;

class RaDataSource
{
	use Nette\SmartObject;

	// -- DB
	/** @var Model\PV_Sessions @inject */
	public $pv_sessions;
	/** @var Model\PV_Devices @inject */
	public $pv_devices;
	/** @var Model\PV_Sensors @inject */
	public $pv_sensors;
	/** @var Model\Measures @inject */
	public $pv_measures;

	/** @var Nette\Database\Explorer */
	private $database;

	public function __construct(
		Nette\Database\Explorer $database
	) {
		$this->database = $database;
	}





	public function createLoginaSession($deviceId, $hash, $key, $remoteIp)
	{
		$this->database->query('INSERT INTO prelogin', [
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => new DateTime,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		return $this->database->getInsertId();
	}

	//TODO: Bude smazano - je pro stary login
	/**
	 * Delete older sessions for the same device
	 * Create session
	 * Set first_login and last_login properties of device
	 * 
	 * Returns session ID
	 */
	public function createSession($deviceId, $saveFirstLogin, $hash, $key, $remoteIp, $appName)
	{
		$this->database->query('DELETE FROM sessions WHERE device_id = ?', $deviceId);

		$now = new DateTime;

		$this->database->query('INSERT INTO sessions', [
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => $now,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		$id = $this->database->getInsertId();

		$values = array();
		$values['last_login'] = $now;
		$values['app_name'] = $appName;
		if ($saveFirstLogin) {
			$values['first_login'] = $now;
		}
		$this->database->query('UPDATE devices SET', $values, 'WHERE id = ?', $deviceId);

		return $id;
	}


	public function createSessionV2(
		$preloginSessionId,
		$deviceId,
		$saveFirstLogin,
		$hash,
		$key,
		$remoteIp,
		$appName,
		$uptime,
		$rssi
	) {
		$this->database->query('DELETE FROM prelogin WHERE id = ?', $preloginSessionId);
		$this->database->query('DELETE FROM sessions WHERE device_id = ?', $deviceId);

		$now = new DateTime;

		$this->database->query('INSERT INTO sessions', [
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => $now,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		$id = $this->database->getInsertId();

		$values = [];
		$values['last_login'] = $now;
		$values['app_name'] = $appName;
		$values['rssi'] = $rssi;
		$values['uptime'] = $uptime;
		if ($saveFirstLogin) {
			$values['first_login'] = $now;
		}
		$this->database->query('UPDATE devices SET', $values, 'WHERE id = ?', $deviceId);

		return $id;
	}

	public function createSession_pv(
		$deviceId,
		$saveFirstLogin,
		$hash,
		$key,
		$remoteIp,
		$appName,
		$uptime,
		$rssi
	) {
		$this->pv_sessions->findBy(['device_id' => $deviceId])->delete();

		$now = new DateTime;

		$row = $this->pv_sessions->add([
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => $now,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		$values = [
			'last_login' 	=> $now,
			'app_name' 		=> $appName,
			'rssi' 				=> $rssi,
			'uptime' 			=> $uptime
		];
		if ($saveFirstLogin) {
			$values['first_login'] = $now;
		}
		$this->pv_devices->get($deviceId)->update($values);
		
		return $row->id;
	}

	/**
	 * Check login session validity. 
	 * Throws NoSessionException when session is not valid.
	 * Returns SessionDevice.
	 */
	public function checkLoginSession($sessionId, $sessionHash): Model\SessionDevice
	{
		$session = $this->database->fetch('SELECT hash, device_id, started, session_key FROM prelogin WHERE id = ?', $sessionId);
		if ($session == NULL) {
			throw new NoSessionException("session {$sessionId} not found");
		}

		if (strcmp($session->hash, $sessionHash) != 0) {
			throw new NoSessionException("bad hash");
		}

		$now = new DateTime;
		// zivotnost session 1 den
		if ($now->diff($session->started)->i > 5) {
			throw new NoSessionException("session expired");
		}

		$rc = new Model\SessionDevice();
		$rc->sessionId = $sessionId;
		$rc->sessionKey = $session->session_key;
		$rc->deviceId = $session->device_id;

		return $rc;
	}

	/**
	 * Založenie alebo aktualizácia senzoru
	 */
	public function processChannelDefinition($sessionDevice, $channel, $devClass, $valueType, $msgRate, $name, $factor)
	{
		$sensor = $this->database->fetch(
			'SELECT id, channel_id FROM sensors WHERE device_id = ? AND name = ?',
			$sessionDevice->deviceId,
			$name
		);

		if ($sensor == NULL) {
			// neexistuje, založenie

			if ($factor === NULL) {
				$process = 0;
			} else {
				$process = 1;
			}

			$this->database->query('INSERT INTO sensors', [
				'device_id' => $sessionDevice->deviceId,
				'channel_id' => $channel,
				'name' => $name,
				'id_device_classes' => $devClass,
				'id_value_types' => $valueType,
				'msg_rate' => $msgRate,
				'preprocess_data' => $process,
				'preprocess_factor' => $factor
			]);
		} else {
			// existuje
			if ($sensor->channel_id != $channel) {
				// existuje, ale ma zlý channel_id -> nastaviť
				$this->database->query('UPDATE sensors SET ', [
					'channel_id' => $channel
				], 'WHERE id = ?', $sensor->id);
			}
		}

		// a nastaviť NULL na channel_id na ostatných záznamoch rovnakého zariadenia s rovnakým channel_id
		$this->database->query('UPDATE sensors SET ', [
			'channel_id' => null
		], ' WHERE device_id = ? AND channel_id = ? AND name <> ?', $sessionDevice->deviceId, $channel, $name);
	}

	/**
	 * Vloženie záznamu zo senzoru do 'measures'
	 */
	public function saveData(Model\SessionDevice $sessionDevice, Table\ActiveRow $sensor, string $messageTime, float $numVal, string $remoteIp, float $value_out, int $impCount, string $dataSession)
	{
		//$msgTime = new DateTime;
		//$msgTime->setTimestamp(time() - $timeDiff);

		$this->pv_measures->save(0, [
			'sensor_id' => $sensor->id,
			'data_time' => $messageTime,
			'server_time' => new DateTime,
			's_value' => $numVal,
			'session_id' => $sessionDevice->sessionId,
			'remote_ip' => $remoteIp,
			'out_value' => $value_out
		]);

		$values = [];
		$values['last_data_time'] = $messageTime;
		if ($sensor['device_class'] != 3) {
			$values['last_out_value'] = $value_out;
		}
		if ($dataSession != '') {
			$values['imp_count'] = $impCount;
			$values['data_session'] = $dataSession;
		}
		//$this->pv_sensors->find($sensor->id)
		//->where('(last_data_time IS NULL) OR (last_data_time < ?)', $messageTime)
		//->update($values);
		$this->pv_sensors->findBy(['id' => $sensor->id, '(last_data_time IS NULL) OR (last_data_time < ?)' => $messageTime])->update($values);
	}


	public function saveBlob($sessionDevice, $time, $description, $extension, $filesize, $remoteIp)
	{
		$msgTime = new DateTime;
		$msgTime->setTimestamp($time);

		$this->database->query('INSERT INTO blobs ', [
			'device_id' => $sessionDevice->deviceId,
			'data_time' => $msgTime,
			'server_time' => new DateTime,
			'description' => $description,
			'extension' => $extension,
			'session_id' => $sessionDevice->sessionId,
			'remote_ip' => $remoteIp,
			'filesize' => $filesize
		]);

		return $this->database->getInsertId();
	}

	public function updateBlob($rowId, $fileName)
	{
		$values = array();
		$values['filename'] = $fileName;
		$values['status'] = 1;
		$this->database->query('UPDATE blobs SET', $values, 'WHERE id = ?', $rowId);
	}

	public function getUpdate($deviceId, $appId)
	{
		return $this->database->fetch('
			SELECT *
			FROM updates 
			WHERE device_id = ? AND fromVersion = ?
		', $deviceId, $appId);
	}

	public function getUpdateById($updateId)
	{
		return $this->database->fetch('
			select u.id as update_id, u.device_id, u.fileHash, s.id as session_id, s.hash, s.session_key
			from updates u
			left outer join sessions s
			on s.device_id = u.device_id
			where u.id=?
		', $updateId);
	}


	public function setUpdateTime($updateId)
	{
		$this->database->query('UPDATE updates SET', [
			'downloaded' => new \DateTime(),
		], 'WHERE id = ?', $updateId);
	}


/** ********************* @DEPRECATED ********************************* */

	/**
	 * Load information about DEVICE
	 * @deprecated don't use
	 */
	public function getDeviceInfoByLogin(string $login): Table\ActiveRow
	{
		return $this->pv_devices->findOneBy(['name'=>$login]);
		//return $this->database->fetch('SELECT * FROM devices WHERE name = ?', $login);
	}
	/** @deprecated don't use */
	public function getDeviceInfoById(int $id): Table\ActiveRow
	{
		return $this->pv_devices->find($id);
		//return $this->database->fetch('SELECT * FROM devices WHERE id = ?', $id);
	}
	/** @deprecated don't use */
	public function badLogin($deviceId)
	{
		$this->pv_devices->badLogin($deviceId);
		//$this->database->query('UPDATE devices SET', [
		//	'last_bad_login' => new DateTime
		//], 'WHERE id = ?', $deviceId);
	}
	/** @deprecated don't use */
	public function deleteConfigRequest($deviceId)
	{
		$this->pv_devices->deleteConfigRequest($deviceId);
		//$this->database->query('UPDATE devices SET config_data = NULL WHERE id = ?', $deviceId);
	}
}
