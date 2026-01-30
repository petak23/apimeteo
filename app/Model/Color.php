<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Utils\Image;

/**
 * Definice barvy
 * Prevzaté z RatatoskrIoT
 * Posledna zmena(last change): 29.01.2026
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>, RatatoskrIoT
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @version 1.0.0
 */
class Color
{
	use Nette\SmartObject;

	/** @var int */
	public $r = 0;
	/** @var int */
	public $g = 0;
	/** @var int */
	public $b = 0;
	
	public function __construct(int $r = 0, int $g = 0, int $b = 0 )
	{
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
	}

	public function getColor(): array
	{
		return Image::rgb($this->r, $this->g, $this->b);
	}

	public function getHtmlColor(): string
	{
		return '#' . substr('00000' . dechex( $this->r<<16 | $this->g<<8 | $this->b ), -6 );
	}

	public function getTextColor(): string
	{
		return "{$this->r},{$this->g},{$this->b}";
	}

	public static function parseColor( $colorString, bool $return_as_array = false): Color|array
	{
		$pars = explode( ',' , $colorString );
		if( sizeof($pars)!=3 ) {
			throw new \Exception( "Can't parse [{$colorString}] to color.");
		}
		$r = intval( $pars[0] );
		$g = intval( $pars[1] );
		$b = intval( $pars[2] );
		return $return_as_array ? ["r"=>$r, "g"=>$g, "b"=>$b] : new Color( $r, $g, $b );
	}

	public static function parseHexColor( $colorString ): Color
	{
		$r = hexdec( substr($colorString,1,2) );
		$g = hexdec( substr($colorString,3,2) );
		$b = hexdec( substr($colorString,5,2) );
		return new Color( $r, $g, $b );
	}
}



