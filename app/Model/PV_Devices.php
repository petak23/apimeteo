<?php

declare(strict_types=1);

namespace App\Model;

use App\Model;
use App\Services\Logger;
use Nette;
use Nette\Utils\DateTime;

/**
 * Model, ktory sa stara o tabulku devices
 * 
 * Posledna zmena 24.07.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.6
 */
class PV_Devices
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
		Model\PV_Sensors $pv_sensors,
		Model\PV_Sessions $sessions
	) {
		$this->devices = $database->table("devices");
		$this->measures = $database->table("measures");
		$this->sumdata = $database->table("sumdata");
		$this->sessions = $sessions;
		$this->pv_sensors = $pv_sensors;
	}

	public function getDevicesUser($userId): VDevices
	{
		$rc = new VDevices();
		// načítame zariadenia

		$result = $this->devices->where(['user_id' => $userId])->order('name ASC');

		foreach ($result as $row) {
			$dev = new VDevice($row);
			if ($dev->attrs->last_bad_login != NULL) {
				if ($dev->attrs->last_login != NULL) {
					$lastLoginTs = (DateTime::from($dev->attrs->last_login))->getTimestamp();
					$lastErrLoginTs = (DateTime::from($dev->attrs->last_bad_login))->getTimestamp();
					if ($lastErrLoginTs >  $lastLoginTs) {
						$dev->problem_mark = true;
					}
				} else {
					$dev->problem_mark = true;
				}
			}
			// Pridám zariadenie a k nemu načítam senzory
			$rc->addWithSensors($dev, $this->pv_sensors->getDeviceSensors($row->id, $row->monitoring));
		}
		return $rc;
	}

	/** 
	 * Pridanie zariadenia
	 *    */
	public function createDevice($values)
	{
		return $this->devices->insert($values);
	}

	/** Nájdenie zariadenia podľa id */
	public function getDevice(int $deviceId): ?Nette\Database\Table\ActiveRow
	{
		return $this->devices->get($deviceId);
	}

	/** Nájdenie zariadenia podľa id */
  public function getDeviceBy(array $by): ?Nette\Database\Table\ActiveRow
  {
    return $this->devices->where($by)->limit(1)->fetch();
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
	public function addWithSensors(VDevice $device, ?Nette\Database\Table\Selection $sensors): void
	{
		$this->devices[$device->attrs['id']] = $device;
		if ($sensors != null && $sensors->count()) {
			foreach ($sensors as $s) {
				$this->devices[$device->attrs['id']]->addSensor($s);
			}
		}
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

	public function __construct(Nette\Database\Table\ActiveRow $attrs)
	{
		$this->attrs = $attrs;
	}

	public function addSensor(Nette\Database\Table\ActiveRow $sensorAttrs): void
	{
		$this->sensors[$sensorAttrs->id] = $sensorAttrs;
	}
}
