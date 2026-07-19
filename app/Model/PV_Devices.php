<?php

declare(strict_types=1);

namespace App\Model;

use App\Model;
use App\Services\Logger;
use Nette;
use Nette\Database;
use Nette\Utils\DateTime;
use function is_array, array_merge;

/**
 * Model, ktory sa stara o tabulku devices
 * 
 * Posledna zmena 19.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.1.2
 */
class PV_Devices
{
	use Nette\SmartObject;

	private Database\Table\Selection $devices;
	private Model\PV_Sessions $pv_sessions;
	private Database\Table\Selection $measures;
	private Database\Table\Selection $sumdata;

	private Model\PV_Sensors $pv_sensors;

	public function __construct(
		Database\Explorer $database,
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

		if ($result->count() > 0) {
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
			return $return_as_array ? ['status' => 200, 'message' => "", 'data' => $rc->returnAsArray()] : $rc;
		} else {
			// Žiadne zariadenia
			return ['status' => 200, 'message' => "Pre užívateľa s id: " . $userId. " neboli nájdené žiadne zariadenia.", 'data' => null];
		}
	}

	/** 
	 * Pridanie zariadenia
	 *    */
	public function createDevice(iterable|Database\Table\Selection $values, bool $return_as_array = false): Database\Table\ActiveRow|array
	{
		$d = $this->devices->insert($values);
		$d = $return_as_array ? $d->toArray() : $d;
		return $d;
	}

	public function getDeviceSimple(int $deviceId): Database\Table\ActiveRow|array
	{
		if (($_device = $this->devices->get($deviceId)) == null) {
			return ['status' => 404, 'message' => "Požadované zariadenie s id='{$deviceId}' som nenašiel.", 'error_n' => 1, 'device_id' => $deviceId];
		}

		return $_device;
	}

	/** Nájdenie zariadenia podľa id */
	public function getDevice(
		int $deviceId,
		bool $with_sensors = false,
		bool $return_as_array = false ): VDevice|array 
	{
		$_device = $this->getDeviceSimple($deviceId);

		return is_array($_device) && $_device['status'] == 404 ? $_device : $this->_deviceInfo($_device, $with_sensors, $return_as_array);
	}

	

	/** Nájdenie zariadenia podľa poľa $by = ['pole'=>'hodnota'] */
  public function getDeviceBy(
		array $by,
		bool $with_sensors = false,
		bool $return_as_array = false): VDevice|array
  {
		if (($_device = $this->devices->where($by)->limit(1)->fetch()) == null) {
			return ['status' => 404, 'message' => "Hľadané zariadenie som nenašiel.", 'error_n' => 2, 'by' => $by];
		}

    return $this->_deviceInfo($_device, $with_sensors, $return_as_array);
  }

	/**
	 * Vráti inpo o zariadení v definovanom formáte
	 * @param Database\Table\ActiveRow $device
	 * @param bool $with_sensors
	 * @param bool $return_as_array
	 * @return array{data: array, status: int|Model\VDevice}
	 */
	private function _deviceInfo(
		Database\Table\ActiveRow $device,
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
			$_d['first_login'] = $_d['first_login'] != null ? $_d['first_login']->format('d.m.Y H:i:s') : null;
			$_d['last_login'] = $_d['last_login'] !=null ? $_d['last_login']->format('d.m.Y H:i:s') : null;
			$_d['uptime_readable'] = $this->secondsToTime((int)($_d['uptime']/1000)); // TODO: prevod z ms na s dočasný!!!
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

		// zmazať data
		if ($sens->count()) {
			Logger::log('webapp', Logger::DEBUG,  "Delete measures device {$id}");
			$this->measures->where("sensor_id", $sens)->delete();

			Logger::log('webapp', Logger::DEBUG,  "Delete sumdata device {$id}");

			$this->sumdata->where("sensor_id in ?", $sens)->delete();
			
			Logger::log('webapp', Logger::DEBUG,  "Delete device {$id}");

			// zmazať senzory a zariadenia
			$sens->delete();
		}

		$this->devices->get($id)->delete();

		Logger::log('webapp', Logger::DEBUG,  "Delete OK.");
	}

	
	private function secondsToTime(int $inputSeconds)
	{
		$secondsInAMinute = 60;
		$secondsInAnHour = 3600;
		$secondsInADay = 86400;

		// Extract days
		$days = floor($inputSeconds / $secondsInADay);

		// Extract hours
		$hourSeconds = $inputSeconds % $secondsInADay;
		$hours = floor($hourSeconds / $secondsInAnHour);

		// Extract minutes
		$minuteSeconds = $hourSeconds % $secondsInAnHour;
		$minutes = floor($minuteSeconds / $secondsInAMinute);

		// Extract the remaining seconds
		$remainingSeconds = $minuteSeconds % $secondsInAMinute;
		$seconds = ceil($remainingSeconds);

		// Format and return
		$timeParts = [];
		$sections = [
			'd' => (int)$days,
			'hod' => (int)$hours,
			'min' => (int)$minutes,
			'sec' => (int)$seconds,
		];

		foreach ($sections as $name => $value) {
			if ($value > 0) {
				$timeParts[] = $value . ' ' . $name;
			}
		}

		return implode(', ', $timeParts);
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
		$id = is_array($device->attrs) ? $device->attrs['id'] : $device->attrs->id;
		$this->devices[$id] = $device;
	}

	public function get(int $id): VDevice
	{
		return $this->devices[$id];
	}

	/** Pridanie zariadenia aj so senzormi */
	public function addWithSensors(
		VDevice $device,
		Database\Table\Selection $sensors,
		bool $return_sensors_as_array = false
	): void {
		$id = is_array($device->attrs) ? $device->attrs['id'] : $device->attrs->id;
		$this->devices[$id] = $device;
		if ($sensors != null && $sensors->count()) {
			foreach ($sensors as $s) {
				$this->devices[$id]->addSensor($s, $return_sensors_as_array);
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

	/** Kompletné data o zariadení */
	public Database\Table\ActiveRow|null $attrs;

	/** Príznak problému */
	public bool $problem_mark = false;

	/** Pole senzorov zariadenia */
	public array $sensors = [];

	public function __construct(Database\Table\ActiveRow|null $attrs = null, bool $return_as_array = false)
	{
		$this->attrs = $return_as_array ? $attrs->toArray() : $attrs;
	}

	public function addSensor(Database\Table\ActiveRow $sensorAttrs, bool $return_as_array = false): void
	{
		$out = [];
		if ($return_as_array) {
			$out = array_merge(
				['value_unit' => $sensorAttrs->value_types->unit],
				$sensorAttrs->toArray()
			);
		}
		$this->sensors[$sensorAttrs->id] = $return_as_array ? $out : $sensorAttrs;
	}
}
