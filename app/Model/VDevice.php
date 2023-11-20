<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Objekt jedného zariadenia 
 * 
 * Posledna zmena 20.11.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class VDevice
{
	use Nette\SmartObject;

	/** @var Nette\Database\Table\ActiveRow|null Kompletné data o zariadení */
	public $attrs;

	/** @var bool Príznak problému */
	public $problem_mark = false;

	/** @var array Pole senzorov zariadenia */
	public $sensors = [];

	public function __construct(Nette\Database\Table\ActiveRow|null $attrs = null, bool $return_as_array = false)
	{
		$this->attrs = $return_as_array ? $attrs->toArray() : $attrs;
	}

	public function addSensor(Nette\Database\Table\ActiveRow $sensorAttrs, bool $return_as_array = false): void
	{
		if ($return_as_array) {
			$out = array_merge(
				['value_unit' => $sensorAttrs->value_types->unit],
				$sensorAttrs->toArray()
			);
		}
		$this->sensors[$sensorAttrs->id] = $return_as_array ? $out : $sensorAttrs;
	}
}
