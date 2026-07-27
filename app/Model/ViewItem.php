<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

use function sizeof, strlen;

/**
 * Položka pohledu
 * Prevzaté z RatatoskrIoT
 * Posledna zmena 25.03.2026
 * Úprava pre APIMeteo. 
 */
class ViewItem
{
	use Nette\SmartObject;

	/**
	 * Pole senzorov (Nette\Database\Row). Každý senzor má vlastnosti:
	 * 
	 * id	device_id	channel_id	name	device_class	value_type	msg_rate	desc	display_nodata_interval	
	 * preprocess_data	preprocess_factor	
	 * dev_name	dev_desc dev_id
	 * unit
	 */
	public array $sensors;

	public int $axisY;
	public int $source;
	public string $sourceDesc;
	public array $colors;

	// len pre editáciu
	public int $id;
	public int $vorder;

	//Len pre inventory
	public array $sensorIds;

	public function isKompozit()
	{
		return sizeof($this->sensors)>1;
	}

	public function getSensorName(): string
	{
		return "{$this->sensors[0]['dev_name']}:{$this->sensors[0]['name']}";
	}

	public function getSensorsName(): string
	{
		$out = "";
		foreach( $this->sensors as $sensor ) {
			if( strlen($out)>0 ) {
				$out .= "+";
			}
			$out .= "{$sensor['dev_name']}:{$sensor['name']}";
		}
		return $out;
	}

	public function getSensorsDesc(): string
	{
		$out = "";
		foreach( $this->sensors as $sensor ) {
			if( strlen($out)>0 ) {
				$out .= " | ";
			}
			$out .= $sensor['desc'];
		}
		if( $this->isKompozit() ) {
			$out = "Kompozit: {$out}";
		}
		return $out;
	}

	public function pushSensor(array $sensor)
	{
		$this->sensors[] = $sensor;
	}

	public function toArray( bool $detailed = false ): array
	{
		$vi = [
			"source" 			=> $this->source,
			"source_desc" => $this->sourceDesc,
			"axisY" 			=> $this->axisY,
			"colors" 			=> $this->colors,
			"vorder" 			=> $this->vorder,
			"id" 					=> $this->id,
			"sensors" 		=> $this->sensors,
			"sensorIds" 	=> $this->sensorIds,
		];
		if( $detailed ) {
			$vi["name"] = $this->getSensorsDesc();
			$vi["sensor_name"] = $this->getSensorsName();
			$vi["unit"] = $this->getUnit();
		}
		
		return $vi;
	}

	public function getUnit(): ?string 
	{
		return isset($this->sensors[0]) ? $this->sensors[0]->unit : null;
	}

	public function setColor( int $nr, string $colorText, bool $return_as_array = false )
	{
		$this->colors[$nr] = Color::parseColor( $colorText, $return_as_array );
	}

	public function getColor( int $nr )
	{
		if( ! isset($this->colors[$nr]) ) {
			throw new \Exception( "Color #{$nr} has not been defined.");
		}
		return $this->colors[$nr];
	}

	public function __construct()
	{
		$this->colors=[];
		$this->sensors=[];
	}

	public function toString(): string
	{
		return "Y:{$this->axisY} src:{$this->source}";
	}
}
