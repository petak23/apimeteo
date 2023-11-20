<?php

declare(strict_types=1);

namespace App\Model;

use App\Services\Logger;
use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * Model, ktory sa stara o tabulku devices
 * 
 * Posledna zmena 09.11.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.6
 */
class Devices
{
	use Nette\SmartObject;

	/** @var Database\Table\Selection */
	private $devices;
	/** @var Database\Table\Selection */
	private $sessions;
	/** @var Database\Table\Selection */
	private $measures;
	/** @var Database\Table\Selection */
	private $sumdata;

	/** @var Database\Table\Selection */
	private $sensors;

	private $pv_sensors;

	public function __construct(
		Nette\Database\Explorer $database,
		Sensors $pv_sensors,
		Sessions $sessions
	) {
		$this->devices = $database->table("devices");
		$this->measures = $database->table("measures");
		$this->sumdata = $database->table("sumdata");
		$this->sessions = $sessions;
		$this->pv_sensors = $pv_sensors;
	}

	public function getDevicesUser(int $userId, bool $return_as_array = false): VDevices|array
	{
		$rc = new VDevices();
		// načítame zariadenia

		$result = $this->devices->where(['user_id' => $userId])->order('id ASC');

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

	/** Pridanie zariadenia */
	public function createDevice($values, bool $return_as_array = false): ActiveRow|array
	{
		$d = $this->devices->insert($values);
		$d = $return_as_array ? $d->toArray() : $d;
		return $d;
	}

	/** Info o zariadení */
	public function getDevice(
		int $deviceId,
		bool $with_sensors = false,
		bool $return_as_array = false
	): VDevice|array {

		if (($_t = $this->devices->get($deviceId)) == null) {
			return ['error' => "Device not found", 'error_n' => 1, 'device_id' => $deviceId];
		}

		$d = new VDevice($this->devices->get($deviceId));
		if ($with_sensors) {
			// Pridám zariadenie a k nemu načítam senzory
			$sensors = $this->pv_sensors->getDeviceSensors($deviceId, $d->attrs->monitoring);
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
			$_d['first_login'] = $d->attrs->first_login->format('d.m.Y H:i:s');
			$_d['last_login'] = $d->attrs->last_login->format('d.m.Y H:i:s');
			$d = $_d;
		}
		return $d;
	}

	public function deleteDevice($id)
	{
		Logger::log('webapp', Logger::DEBUG,  "Mazu session device {$id}");

		// nejprve zmenit heslo a smazat session, aby se uz nemohlo prihlasit
		$this->devices->get($id)->update(['passphrase' => 'x']);
		$this->sessions->deleteSession($id);

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
		/*$this->database->query('
						DELETE from devices
						WHERE id = ?
				', $id);*/

		Logger::log('webapp', Logger::DEBUG,  "Delete OK.");
	}
}
// ------------------------------------  End class Devices

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

	/** @var Nette\Database\Table\ActiveRow Kompletné data o zariadení */
	public $attrs;

	/** @var bool Príznak problému */
	public $problem_mark = false;

	/** @var array Pole senzorov zariadenia */
	public $sensors = [];

	public function __construct(Nette\Database\Table\ActiveRow $attrs, bool $return_as_array = false)
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
