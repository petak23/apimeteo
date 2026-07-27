<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

use function sizeof;

/**
 * SensorDataSeries
 */
class SensorDataSeries
{
	use Nette\SmartObject;

	/**
	 * Data sensoru
	 * 
	 * Properties: id	device_id	channel_id	name	device_class	id_value_types	msg_rate	desc	display_nodata_interval	 preprocess_data	preprocess_factor dev_name	dev_desc
	 */
	public array $firstSensor;

	/**
	 * Pole objektu ChartPoint.
	 * Objekty vkladat pres pushPoint() !
	 */
	public array $points;


	/**
	 * Max hodnota pre škálovanie grafu
	 */
	public ?float $maxVal;

	/**
	 * Min hodnota pre škálovanie grafu
	 */
	public ?float $minVal;


	/**
	 * Relatívny čas predchádzajúceho bodu. Pre pushPoint.
	 */
	private ?int $prevPointTime;

	/**
	 * Vlozi bod do pole. 
	 * Vyplní, či je prepojený s predchádzajúcim alebo nie.
	 * Nastavuje max/min hodnoty v serii.
	 */
	public function pushPoint(ChartPoint $point, bool $dailySum = FALSE)
	{
		if ($point->value === null) {
			return;
		}

		$point->connectedFromPrevious = FALSE;

		$maxDiff = $dailySum ? 180000 : $this->firstSensor['display_nodata_interval'];

		if ($this->prevPointTime != null) {
			// ak existuje predchádzajúci bod a je časovo bližšie než zobrazovací limit, označíme si, že je prepojený
			if (($point->relativeTime - $this->prevPointTime) < $maxDiff) {
				$point->connectedFromPrevious = TRUE;
			}
		}

		if ($this->minVal === null || $point->value < $this->minVal) {
			$this->minVal = $point->value;
		}

		if ($this->maxVal === null || $point->value > $this->maxVal) {
			$this->maxVal = $point->value;
		}

		$this->prevPointTime = $point->relativeTime;
		$this->points[] = $point;

		// Debugger::log( ' + ' . $point->toString() );
	}

	public function __construct(?array $sensor = null)
	{
		$this->firstSensor = $sensor;
		$this->points = [];
		$this->maxVal = null;
		$this->minVal = null;
		$this->prevPointTime = null;
	}

	/**
	 * Pocet zaznamu v poli
	 */
	public function size(): int
	{
		return sizeof($this->points);
	}

	public function toString($verbose = FALSE): string
	{
		$rc = "SensorDataSeries [sensor {$this->firstSensor['id']} '{$this->firstSensor['dev_name']}:{$this->firstSensor['name']}'; ct={$this->size()}";
		$rc .= " min={$this->minVal} max={$this->maxVal}";
		if ($verbose) {
			$rc .= "; data:";
			foreach ($this->points as $point) {
				$rc .= ' ' . $point->toString();
			}
		}
		$rc .= ' ]';

		return $rc;
	}
}
