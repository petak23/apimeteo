<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Database\Table;
use Nette\Utils\DateTime;

class CrontaskDataSource
{
	use Nette\SmartObject;

	/** @var Nette\Database\Explorer */
	private $database;

	private array $cronAllowed = [];

	public function __construct(array $cronAllowed, Nette\Database\Explorer $database)
	{
		$this->database = $database;
		$this->cronAllowed = $cronAllowed;
	}


	/**
	 * Vraci:
	 * sensor_id, data_time
	 */
	public function getRecordsForProcessing($batchSize)
	{
		return $this->database->fetchAll("
			select * 
			from
			(
			select 
			sensor_id, data_time 
			from measures
			where status=0
			order by data_time asc
			limit $batchSize  
			) d

			order by sensor_id, data_time
			");
	}

	public function getCronAllowed()
	{
		return $this->cronAllowed;
	}

	public function getImagesForProcessing()
	{
		return $this->database->fetchAll("
		select * 
		from blobs
		where extension = 'jpg'
		and description = 'camera'
		and status = 1
		order by id asc
		limit 30
		");
	}

	public function updateImageAll($id, $desc)
	{
		$this->database->query('UPDATE blobs SET ', [
			'status' => 2,
			'description' => $desc
		], 'WHERE id = ?', $id);
	}

	public function updateImageStatus($id)
	{
		$this->database->query('UPDATE blobs SET ', [
			'status' => 2
		], 'WHERE id = ?', $id);
	}


	/**
	 * id	device_id	channel_id	name	device_class	value_type	msg_rate	desc	display_nodata_interval	preprocess_data	preprocess_factor
	 * @deprecated - používat getSensor() z Model\PV_Sensors, který vrací i další potřebná pole pro zpracování notifikací
	 * @return array|false|Nette\Database\IRow
	 */
	public function getSensor($sensorId)
	{
		return $this->database->fetch('SELECT * FROM sensors WHERE id = ?', $sensorId);
	}

	/** */
	public function getRecordsForSensorHour(int $sensorId, string $date, string $hour): Table\Selection
	{
		$dateFrom = $date . " " . $hour . ":00:00";
		$dateTo = $date . " " . $hour . ":59:59";

		return $this->database->table('measures')
								->whereOr([
									'sensor_id' => $sensorId,
									'data_time >= ' => $dateFrom,
									'data_time <= ' => $dateTo
								]);
	}

	/**
	 * Nastaví status merania s id na 1
	 * @param int $id Id merania, ktoré se má aktualizovať
	 * @return void
	 */
	public function updateMeasure(int $id): void
	{
		$this->database->table('measures')->where('id', $id)->update([
			'status' => 1
		]);
	}

	public function createSummary(
		int $sensorId,
		string $date,
		string|int $hour,
		float|null $min,
		string|null $min_time,
		float|null $max,
		string|null $max_time,
		float|null $avg,
		float|null $sum,
		int $sum_type,
		int $count
	) {

		$this->database->table('sumdata')->where([
			'sensor_id' => $sensorId,
			'rec_date' => $date,
			'sum_type' => $sum_type,
			'rec_hour' => $hour
		])->delete();

		$this->database->table('sumdata')->insert([
			'sensor_id' => $sensorId,
			'sum_type' => $sum_type,
			'rec_date' => $date,
			'rec_hour' => $hour,
			'min_val' => $min,
			'min_time' => $min_time,
			'max_val' => $max,
			'max_time' => $max_time,
			'avg_val' => $avg,
			'sum_val' => $sum,
			'status' => 0,
			'ct_val' => $count
		]);
	}


	/** 
	 * Pre impulzné senzory dá celkovú dennú sumu do sensor['last_out_value']
	 * @param int $sensorId Id senzoru, ktorému sa má aktualizovať hodnota
	 * @param float $sum Celková denná suma
	 * @return void
	 */
	public function updateSensorValue(int $sensorId, float $sum): void
	{
		$this->database->table('sensors')->where('id', $sensorId)->update([
			'last_out_value' => $sum
		]);
	}

	/**
	 * Nastaví status pre záznam sumy s id na 1
	 * @param int $id Id sumy, které se má aktualizovat
	 * @return void
	 */
	public function updateSumdata(int $id): void
	{
		$this->database->table('sumdata')->where('id', $id)->update([
			'status' => 1
		]);
	}

	/**
	 *  id	hash	device_id	started	remote_ip	session_key
	 */
	public function getOldPrelogins($limit)
	{
		return $this->database->fetchAll(
			'select * from prelogin
			 where started < ?',
			$limit
		);
	}

	public function markDeviceLoginProblem($deviceId, $ltime)
	{
		$this->database->query('UPDATE devices SET ', [
			'last_bad_login' => $ltime
		], 'WHERE id = ? AND ( (last_login is NULL) OR (last_login < ?) ) ', $deviceId, $ltime);
	}

	public function deletePrelogin($id)
	{
		$this->database->query('DELETE from prelogin WHERE id = ? ', $id);
	}

	public function getSensors(): Table\Selection
	{
		return $this->database->table('sensors')
			//->select('sensors.*, devices.monitoring, devices.name AS dev_name')
			//->join('devices', 'sensors.device_id = devices.id')
			->where('last_out_value IS NOT NULL');
			//->fetchAll();
	}


	public function updateSensorsWarnings($sensor)
	{
		$this->database->query('UPDATE sensors SET ', [
			'warn_max_fired' => $sensor['warn_max_fired'],
			'warn_min_fired' => $sensor['warn_min_fired'],
			'warn_max_sent' => $sensor['warn_max_sent'],
			'warn_min_sent' => $sensor['warn_min_sent'],
			'warn_noaction_fired' => $sensor['warn_noaction_fired'],
		], 'WHERE id = ?', $sensor['id']);
	}


	/**
	 * Vraci: sensor_id, rec_date 
	 */
	public function getSumsForProcessing($batchSize)
	{
		return $this->database->fetchAll("
			select * 
			from
			(
			select sensor_id, rec_date from sumdata
			where sum_type=1
			and status=0
			order by rec_date asc
			limit $batchSize
			) d
			order by sensor_id, rec_date
			");
	}

	/**
	 * vracia: id	sensor_id	sum_type	rec_date	rec_hour	min_val	min_time	max_val	max_time	avg_val	sum_val	status
	 */
	public function getSumsForSensorDay(int $sensorId, string $date): Table\Selection
	{
		return $this->database->table('sumdata')
			->where([
				'sensor_id' => $sensorId,
				'rec_date' => $date,
				'sum_type' => 1
			]);
	}

	/**
	 *  id, username, measures_retention, sumdata_retention, blob_retention
	 */
	public function getAllUserSettings()
	{
		return $this->database->fetchAll("
			select id, username, measures_retention, sumdata_retention, blob_retention 
			from rausers
			");
	}

	public function getSensorIdsForUser($userId)
	{
		$result = $this->database->query("
			select s.id
			from sensors s
			
			left outer join devices d
			on s.device_id = d.id
			
			where d.user_id = ?
		", $userId);

		$ids = array();
		foreach ($result as $row) {
			$ids[] = $row->id;
		}
		return $ids;
	}

	/**
	 * id, data_time, filename
	 */
	public function getBlobsForUser($userId, $purgeDate)
	{
		return $this->database->fetchAll("
			select b.id, b.data_time, b.filename
			from blobs b

			left outer join devices d
			on b.device_id = d.id
			
			where user_id = ?
			and data_time < ?
		", $userId, $purgeDate);
	}

	public function deleteMeasures($sensorIds, $purgeDate)
	{
		/*
		$row = $this->database->fetch( "
			select count(*) as ct from measures
			where data_time < ? 
			and sensor_id in ( ? )
		", $purgeDate, $sensorIds );
		*/

		$result = $this->database->query("
			delete from measures
			where data_time < ? 
			and sensor_id in ( ? )
			and status >= 1
		", $purgeDate, $sensorIds);

		return $result->getRowCount();
	}

	public function deleteSumdata($sensorIds, $purgeDate)
	{
		$result = $this->database->query("
			delete from sumdata
			where rec_date < ? 
			and sensor_id in ( ? )
			and status >= 1
		", $purgeDate, $sensorIds);

		return $result->getRowCount();
	}

	public function deleteBlob($id)
	{
		$result = $this->database->query("
			delete from blobs
			where id = ?
		", $id);

		return $result->getRowCount();
	}


	public function getExportData()
	{
		return $this->database->fetchAll('
			select m.id, m.sensor_id, m.data_time, m.server_time, m.out_value as value, 
			s.device_id as device_id, s.name as sensor_name,
			d.name as device_name, d.user_id
			
			from measures m
			
			left outer join sensors s 
			on m.sensor_id = s.id
			
			left outer join devices d
			on s.device_id = d.id
			
			where m.status=1
			order by m.id asc
			limit 200
		');
	}

	public function rowExported($id)
	{
		$this->database->query('UPDATE measures SET status = 2
			WHERE id = ?', $id);
	}
}
