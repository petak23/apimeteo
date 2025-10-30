<?php

namespace App\Model;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

/**
 * Model, ktorý sa stará o tabuľku sensors
 * 
 * Posledna zmena 30.09.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
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
		return $this->findOneBy(['device_id'=>$deviceId, 'channel_id'=>$channel]);
	}
}
