<?php

namespace App\Model;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;
use App\Services\Logger;

/**
 * Model, ktorý sa stará o tabuľku sensors
 * 
 * Posledna zmena 23.03.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.4
 */
class PV_Sensors extends Table
{

	/** @var string */
	protected $tableName = 'sensors';

	public function getDeviceSensors(int $deviceId, int $monitoring = 0): Selection
	{
		$sensors = $this->findBy(['device_id' => $deviceId])->order('id ASC');

		foreach ($sensors as $sensor) {
			$warningIcon = 0;
			if ($sensor->last_data_time) {
				$utime = (DateTime::from($sensor->last_data_time))->getTimestamp();
				if (time() - $utime > $sensor->msg_rate) {
					$warningIcon = $monitoring == 1 ? 1 : 2;
				}
			}
			if ($warningIcon != $sensor->warning_icon) $sensor->update(['warning_icon' => $warningIcon]);
		}

		return $sensors;
	}

	/**
	 * Info o senzore
	 * @param int $sensorId
	 * @param bool $return_as_array
	 * @return ActiveRow|array|null
	 */
	public function getSensor(int $sensorId, bool $return_as_array = false): ActiveRow|array|null
	{
		$sensor = $this->find($sensorId);
		if ($sensor === null && $return_as_array) {
			return ['status' => 404, 'message' => 'Sensor s id ' . $sensorId . ' sa nenašiel.'];
		}
		if ($return_as_array && $sensor) {
			$out = $sensor->toArray();

			//dumpe($sensor, $out);
			$out['status'] = 200;
			$out['dev_name'] = $sensor->device->name;
			$out['dev_desc'] = $sensor->device->desc;
			$out['user_id'] = $sensor->device->user_id;
			$out['unit'] = $sensor->value_types->unit;
			$sensor = $out;
		}
		return $sensor;
	}

	/**
	 * sensor_id	pocet	name	desc
	 */
	public function getDataStatsMeasures($id)
	{
		return $this->connection->fetchAll('
			select d.*, s.name, s.desc
			from (
				select sensor_id, count(*) as pocet
				from measures
				where sensor_id in (select id from sensors where device_id = ?)
				group by sensor_id
			) d
				
			left outer join sensors s on d.sensor_id = s.id
				
			order by s.name
		', $id);
	}

	/**
	 * sensor_id	pocet	name	desc
	 */
	public function getDataStatsSumdata($id)
	{
		return $this->connection->fetchAll('
						select 
						d.*, s.name, s.desc
						from 
						(
						select sensor_id, count(*) as pocet
						from sumdata
						where 
						sensor_id in (select id from sensors where device_id = ?)
						group by sensor_id
						) d
						
						left outer join sensors s
						on d.sensor_id = s.id
						
						order by s.name
				', $id);
	}

	public function updateSensor($id, $values)
	{
		$outvalues = [];
		$outvalues['desc'] = $values['desc'];
		$outvalues['display_nodata_interval'] = $values['display_nodata_interval'];
		$outvalues['preprocess_data'] = $values['preprocess_data'];
		$outvalues['preprocess_factor'] =  ($values['preprocess_data'] == '1' ? $values['preprocess_factor'] : "1");

		if (isset($values['warn_max'])) {
			$outvalues['warn_max'] = $values['warn_max'];
			$outvalues['warn_max_val'] = ($values['warn_max'] == '1' ? $values['warn_max_val'] : 0);
			$outvalues['warn_max_val_off'] = ($values['warn_max'] == '1' ? $values['warn_max_val_off'] : 0);
			$outvalues['warn_max_after'] = ($values['warn_max'] == '1' ? $values['warn_max_after'] : 0);
			$outvalues['warn_max_text'] = $values['warn_max_text'];
			$outvalues['warn_min'] = $values['warn_min'];
			$outvalues['warn_min_val'] = ($values['warn_min'] == '1' ? $values['warn_min_val'] : 0);
			$outvalues['warn_min_val_off'] = ($values['warn_min'] == '1' ? $values['warn_min_val_off'] : 0);
			$outvalues['warn_min_after'] = ($values['warn_min'] == '1' ? $values['warn_min_after'] : 0);
			$outvalues['warn_min_text'] = $values['warn_min_text'];
		}

		$this->database->query('UPDATE sensors SET ', $outvalues, ' WHERE id = ?', $id);
	}

	/**
	 * Vráti sensor pre daný kanál.
	 */
	public function getSensorByChannel(int $deviceId, int $channel): ActiveRow|null
	{
		return $this->findOneBy(['device_id'=>$deviceId, 'id'=>$channel]); //'channel_id'=>$channel
	}

	public function getSensors(int $userId): array
	{
		$sensors = [];
		$result = $this->findBy(["device.user_id" => $userId]);
		/*$result = $this->connection->query('
						select 
								s.*, 
								d.name as dev_name, d.desc as dev_desc, d.user_id,
								vt.unit
						from sensors s
						left outer join devices d
						on s.device_id = d.id
						left outer join value_types vt
						on s.id_value_types = vt.id
						where d.user_id = ?
						order by vt.unit asc, d.name asc, s.name asc
				', $userId);*/

		//dumpe($result);

		foreach ($result as $row) {
			$sensors[$row->id] = array_merge( $row->toArray(), [
				"dev_name" => $row->device->name,
				"dev_desc" => $row->device->desc,
				"user_id" => $row->device->user_id,
				"unit" => $row->value_types->unit,
				"last_data_time" => $row->last_data_time ? $row->last_data_time->format('d.m.Y H:i:s') : null,
				"warn_noaction_fired" => $row->warn_noaction_fired !=null ? $row->warn_noaction_fired->format('d.m.Y H:i:s') : null,
			]);
		}

		return $sensors;
	}

	
	public function getAndCheckSensorAccess(int $id): array {
		$sensor = $this->getSensor($id, true);
		if (!$sensor) {
			return ["status" => 404, "message" => "Senzor sa nenašiel"];
		} elseif (!$this->user->isLoggedIn()) {
			return ["status" => 401, "message" => "Pre prístup k tomuto senzoru sa musíte prihlásiť!"];
		} elseif ($this->user->id != $sensor['user_id']) {
			Logger::log('audit', Logger::ERROR,
				sprintf("Užívateľ #%s (%s) sa pokúšal o prístup k senzoru #%s, ktorý patrí užívateľovi #%s", $this->user->id, $this->user->getIdentity()->email, $id, $sensor['user_id']));
			$this->user->logout(true);
			return ["status" => 500, "message" => "K tomuto senzoru nemáte oprávnený prístup!"];
		} else {
			return array_merge($sensor, ['status' => 200, 'message' => 'OK']);
		}
	}
}
