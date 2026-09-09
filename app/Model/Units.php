<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Model, ktory sa stara o tabulku value_types
 * 
 * Posledna zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class Units extends Table
{
	protected string $tableName = 'value_types';

	public function getUnits(bool $full_info = false): array
	{
		try {
			$_tmp = $this->findAll()->order('id ASC');
			if ($full_info) {
				$ou = [];
				foreach ($_tmp as $v) {
					$ou[$v->id] = $v->toArray();
				}
				$_tmp = $ou;
			} else {
				$_tmp = $_tmp->fetchPairs("id", "unit");
			}
			return ['status' => 200, 'data' => $_tmp];
		} catch (Nette\Database\DriverException $e) {
			return [
				'status'	=> 500,
				'error' => 'Chyba databázy: ' . $e->getMessage(),
			];
		}
	}
}
