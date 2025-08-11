<?php

declare(strict_types=1);

namespace App\Model;

use App\Model;
use App\Services\Logger;
use Nette;
use Nette\Database;
use Nette\Utils\DateTime;

/**
 * Model, ktory sa stara o tabulku devices
 * 
 * Posledna zmena 03.08.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.1.0
 */
class PV_Devices
{
	use Nette\SmartObject;

	/** @var Database\Table\Selection */
	private $devices;

	/** @var PV_Sessions */
	private $pv_sessions;

	/** @var Database\Table\Selection */
	private $measures;
	/** @var Database\Table\Selection */
	private $sumdata;

	/** @var PV_Sensors */
	private $sensors;

	private $pv_sensors;

	public function __construct(
		Nette\Database\Explorer $database,
		Model\PV_Sensors $pv_sensors,
		Model\PV_Sessions $sessions
	) {
		$this->devices = $database->table("devices");
		$this->measures = $database->table("measures");
		$this->sumdata = $database->table("sumdata");
		$this->pv_sessions = $sessions;
		$this->pv_sensors = $pv_sensors;
	}

	public function getDevicesUser(int $userId, bool $return_as_array = false): VDevices|array
	{
		$rc = new VDevices();
		// načítame zariadenia

		$result = $this->devices->where(['user_id' => $userId])->order('id ASC');

		if ($result->count() > 0)
			foreach ($result as $row) {
				$dev = new VDevice($row, $return_as_array);
				if ($dev->attrs['last_bad_login'] != NULL) {
					if ($dev->attrs['last_login'] != NULL) {
						$lastLoginTs = (DateTime::from($dev->attrs['last_login']))->getTimestamp();
						$lastErrLoginTs = (DateTime::from($dev->attrs['last_bad_login']))->getTimestamp();
						if ($lastErrLoginTs >  $lastLoginTs) {
							$dev->problem_mark = true;
						}
					} else {
						$dev->problem_mark = true;
					}
				}
				// Pridám zariadenie a k nemu načítam senzory
				$rc->addWithSensors($dev, $this->pv_sensors->getDeviceSensors($row->id, $row->monitoring), $return_as_array);
			}
		return $return_as_array ? $rc->returnAsArray() : $rc;
	}

	/** 
	 * Pridanie zariadenia
	 *    */
	public function createDevice($values, bool $return_as_array = false): Database\Table\ActiveRow|array
	{
		$d = $this->devices->insert($values);
		$d = $return_as_array ? $d->toArray() : $d;
		return $d;
	}

	/** Nájdenie zariadenia podľa id */
	public function getDevice(
		int $deviceId,
		bool $with_sensors = false,
		bool $return_as_array = false ): VDevice|array 
	{
		if (($_device = $this->devices->get($deviceId)) == null) {
			return ['status' => 404, 'error' => "Device not found", 'error_n' => 1, 'device_id' => $deviceId];
		}

		return $this->_deviceInfo($_device, $with_sensors, $return_as_array);
	}

	/** Nájdenie zariadenia podľa poľa $by = ['pole'=>'hodnota'] */
  public function getDeviceBy(
		array $by,
		bool $with_sensors = false,
		bool $return_as_array = false): VDevice|array
  {
		if (($_device = $this->devices->where($by)->limit(1)->fetch()) == null) {
			return ['status' => 404, 'error' => "Device not found by...", 'error_n' => 2, 'by' => $by];
		}

    return $this->_deviceInfo($_device, $with_sensors, $return_as_array);
  }

	/**
	 * Vráti inpo o zariadení v definovanom formáte
	 * @param \Nette\Database\Table\ActiveRow $device
	 * @param bool $with_sensors
	 * @param bool $return_as_array
	 * @return array{data: array, status: int|Model\VDevice}
	 */
	private function _deviceInfo(
		Nette\Database\Table\ActiveRow $device,
		bool $with_sensors = false,
		bool $return_as_array = false
	)	: VDevice|array {
		$d = new VDevice($device);
		if ($with_sensors) {
			// Pridám zariadenie a k nemu načítam senzory
			$sensors = $this->pv_sensors->getDeviceSensors($device->id, $d->attrs->monitoring);
			if ($sensors != null && $sensors->count()) {
				foreach ($sensors as $s) {
					$d->addSensor($s, $return_as_array);
				}
			}
		}
		if ($return_as_array) {
			$_d = $d->attrs->toArray();
			$_d['problem_mark'] = $d->problem_mark;
			$_d['sensors'] = $d->sensors;
			$_d['first_login'] = $_d['first_login']->format('d.m.Y H:i:s');
			$_d['last_login'] = $_d['last_login']->format('d.m.Y H:i:s');
			$d = ['status' => 200, 'data'=> $_d];
		}
		return $d;
	}

	/** Zapíš dobu prevádzky alebo dobu bezporuchovosti vo formáte čísla sekúnd */
	public function setUptime(int $deviceId, int $uptime): void
	{
		if ($deviceId > 0 && $uptime > 0) $this->devices->get($deviceId)->update(['uptime' => $uptime]);
	}

	public function badLogin(int $deviceId): void
	{
		$this->devices->get($deviceId)->update(['last_bad_login' => new DateTime ]);
	}

	public function deleteConfigRequest(int $deviceId): void
	{
		$this->devices->get($deviceId)->update(['config_data' => NULL]);
	}

	public function deleteDevice(int $id): void
	{
		Logger::log('webapp', Logger::DEBUG,  "Mažem session device {$id}");

		// nejprve zmenit heslo a smazat session, aby se uz nemohlo prihlasit
		$this->devices->get($id)->update(['passphrase' => 'x']);
		$this->pv_sessions->deleteSession($id);

		$sens = $this->pv_sensors->getDeviceSensors($id);

		// smazat data
		if ($sens->count()) {
			Logger::log('webapp', Logger::DEBUG,  "Delete measures device {$id}");
			$this->measures->where("sensor_id", $sens)->delete();
			/*$this->database->query('
							DELETE from measures  
							WHERE sensor_id in (select id from sensors where device_id = ?)
					', $id);*/

			Logger::log('webapp', Logger::DEBUG,  "Delete sumdata device {$id}");

			$this->sumdata->where("sensor_id in ?", $sens)->delete();
			/*$this->database->query('
							DELETE from sumdata
							WHERE sensor_id in (select id from sensors where device_id = ?)
					', $id);*/

			Logger::log('webapp', Logger::DEBUG,  "Delete device {$id}");

			// smazat senzory a zarizeni
			$sens->delete();
			/*$this->database->query('
							DELETE from sensors
							WHERE device_id = ?
					', $id);*/
		}

		$this->devices->get($id)->delete();

		Logger::log('webapp', Logger::DEBUG,  "Delete OK.");
	}
}
// ------------------------------------  End class PV_Devices

/** 
 * Objekt všetkých zariadení 
 * */
class VDevices
{
	use Nette\SmartObject;

	/** @var array Pole všetkých zariadení */
	public $devices = [];

	public function add(VDevice $device): void
	{
		$this->devices[$device->attrs['id']] = $device;
	}

	public function get(int $id): VDevice
	{
		return $this->devices[$id];
	}

	/** Pridanie zariadenia aj so senzormi */
	public function addWithSensors(
		VDevice $device,
		Nette\Database\Table\Selection $sensors,
		bool $return_sensors_as_array = false
	): void {
		$this->devices[$device->attrs['id']] = $device;
		if ($sensors != null && $sensors->count()) {
			foreach ($sensors as $s) {
				$this->devices[$device->attrs['id']]->addSensor($s, $return_sensors_as_array);
			}
		}
	}

	public function returnAsArray(): array
	{
		$out = [];
		foreach ($this->devices as $k => $v) {
			$out[$k] = $v->attrs;
			$out[$k]['problem_mark'] = $v->problem_mark;
			$out[$k]['sensors'] = $v->sensors;
		}
		return $out;
	}
}

/** 
 * Objekt jedného zariadenia 
 * */
class VDevice
{
	use Nette\SmartObject;

	/** @var Nette\Database\Table\ActiveRow|null Kompletné data o zariadení */
	public $attrs;

	/** @var bool Príznak problému */
	public $problem_mark = false;

	/** @var array Pole senzorov zariadenia */
	public $sensors = [];

	public function __construct(Nette\Database\Table\ActiveRow|null $attrs = null, bool $return_as_array = false)
	{
		$this->attrs = $return_as_array ? $attrs->toArray() : $attrs;
	}

	public function addSensor(Nette\Database\Table\ActiveRow $sensorAttrs, bool $return_as_array = false): void
	{
		if ($return_as_array) {
			$out = array_merge(
				['value_unit' => $sensorAttrs->value_types->unit],
				$sensorAttrs->toArray()
			);
		}
		$this->sensors[$sensorAttrs->id] = $return_as_array ? $out : $sensorAttrs;
	}
}
