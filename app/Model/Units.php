<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Model, ktory sa stara o tabulku value_types
 * 
 * Posledna zmena 03.10.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class Units extends Table
{
	/** @var string */
	protected $tableName = 'value_types';

	public function getUnits(): array
	{
		try {
			$_tmp = $this->findAll()->order('id ASC')->fetchPairs("id", "unit");
			return ['status' => 200, 'data' => $_tmp];
		} catch (Nette\Database\DriverException $e) {
			return [
				'status'	=> 500,
				'error' => 'Chyba databázy: ' . $e->getMessage(),
			];
		}
	}
}
