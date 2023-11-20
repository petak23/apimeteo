<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Model, ktory sa stara o tabulku value_types
 * 
 * Posledna zmena 13.09.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class Units extends Table
{
	/** @var string */
	protected $tableName = 'value_types';

	public function getUnits(): array
	{
		return $this->findAll()->order('id ASC')->fetchPairs("id", "unit");
	}
}
