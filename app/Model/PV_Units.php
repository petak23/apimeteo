<?php

namespace App\Model;

use Nette\Database\Table\Selection;

/**
 * Model, ktory sa stara o tabulku value_types
 * 
 * Posledna zmena 23.10.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class PV_Units extends Table
{
	protected string $tableName = 'value_types';

	public function getUnits(): Selection
	{
		return $this->findAll()->order('id ASC');
	}
}
