<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
//use \Nette\Utils\DateTime;


/**
 * Device session
 */
class ChartPoint
{
	use Nette\SmartObject;
	
	/**
	 * Time from start of interval, sec
	 */
	public $relativeTime;

	/**
	 * Value at this time
	 */
	public $value;

	/**
	 * true = ma byt spojeno s predeslym;
	 * false = nema
	 */
	public $connectedFromPrevious = true;

	public function __construct( int $relativeTime = 0, float $value = 0.0 )
	{
		$this->relativeTime = $relativeTime;
		$this->value = $value;
	}

	public function toString() : string
	{
		return "[t=+{$this->relativeTime} v={$this->value} c=" . ( $this->connectedFromPrevious ? 'Y' : 'N' ) . ']';
	}
}



