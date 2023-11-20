<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model, ktory sa stara o tabulku measures
 * 
 * Posledna zmena 25.09.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class Measures extends Table
{

	/** @var string */
	protected $tableName = 'measures';

	public function getMeasures(int $id_sensor, int $limit = 50): array
	{
		$m = $this->findBy(['sensor_id' => $id_sensor])->order("id DESC")->limit($limit);
		$out = [];
		foreach ($m as $k => $v) {
			$out[] = $v->toArray();
		}
		return $out;
	}

	public function getLastMeasure(int $id_sensor): array
	{
		return $m = $this->findBy(['sensor_id' => $id_sensor])->order("data_time DESC")->limit(1)->fetch()->toArray();
	}
}
