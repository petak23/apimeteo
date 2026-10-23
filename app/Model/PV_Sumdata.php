<?php

namespace App\Model;

use Nette\Database\Table\Selection;

/**
 * Model, ktory sa stara o tabulku sumdata
 * 
 * Posledna zmena 23.10.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class PV_Sumdata extends Table
{
	protected string $tableName = 'sumdata';

	public function getOneDay(int $id_sensor, string $order = 'rec_date DESC', int $limit = 24, bool $return_as_array = false): Selection|array
	{
		$out = $this->findAll()
			->where('sensor_id = ?', $id_sensor)
			->where('sum_type = ?', 1)
			->order($order)->limit($limit);
		if ($return_as_array) {
			$_tmp = [];
			foreach ($out as $row) {
				$r = $row->toArray();
				$_tmp[] = $this->_convDates($r);
			}
			$out = $_tmp;
		}
		return $out;
	}

	public function getOneDaySum(int $id_sensor): array|null
	{
		$_tmp = $this->findBy(['sensor_id' => $id_sensor, 'sum_type' => 2])->order('rec_date DESC')->limit(1)->fetch();
		return $_tmp !== null ? $this->_convDates($_tmp->toArray()) : null;
	}

	private function _convDates(array $input): array
	{
		$input['rec_date'] = $input['rec_date']->format('d.m.Y');
		$input['min_time'] = $input['min_time']->format('%H:%I');
		$input['max_time'] = $input['max_time']->format('%H:%I');
		return $input;
	}
}
