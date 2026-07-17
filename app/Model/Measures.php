<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model, ktory sa stara o tabulku measures
 * 
 * Posledna zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class Measures extends Table
{
	protected string $tableName = 'measures';

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
		$m = $this->findBy(['sensor_id' => $id_sensor])->order("data_time DESC")->limit(1)->fetch();
		if ($m === null) {
			return ['status' => 404, 'message' => 'Meranie pre senzor s id: ' . $id_sensor . ' sa nenašlo.'];
		}
		return $m->toArray();
	}
}
