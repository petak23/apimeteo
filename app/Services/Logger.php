<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Utils\DateTime;

/**
 * Logger s denne rotovanými súbormi.
 * Last change 07.04.2025
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 *
 * Možné je statické použitie:
 *      Logger::log( 'subor', Logger::ERROR , "Správa" ); 
 * ktoré zapíše do 
 *      log/subor.YYYY-MM-DD.txt
 * obsah
 *      HH:MM:SS ERR Správa
 * 
 * Ďalej je možné dynamické použitie:
 *      $logger = new Logger( 'subor' );
 *      $logger->write( Logger::ERROR, "Správa" );
 * ktoré urobí to isté.
 * Ale dynamické použitie umožňuje ďalej toto:
 *      $logger->setContext( 'user1,192.168.32.1' );
 *      $logger->write( Logger::ERROR, "Správa" );
 * a to zapíše
 *      HH:MM:SS ERR [user1,192.168.32.1] Správa
 * Tj. pre paralelné spracovanie dát z viacerích zdrojov je možné je odlíšiť kontextom. 
 * Kontext sa pridáva ku všetkým ďalším ->write() až do okamžiku ->setContext(NULL);
 */
class Logger 
{
	use Nette\SmartObject;

	private $fileBase;

	const
		DEBUG = '-d-',
		INFO = '-i-',
		WARNING = 'WRN',
		ERROR = 'ERR';
	
	public static function log( $fileName, $level, $msg ) 
	{
		$fileBase = __DIR__ . '/../../log/' . $fileName ;

		$time = new DateTime();
		$namePart = $time->format('Y-m-d');
		$timePart = $time->format('H:i:s');
		$file = "{$fileBase}.{$namePart}.txt";

		if( is_array($msg) ) {
			$out = array();
			foreach ($msg as $k => $v) { 
				$out[] = "$k=$v"; 
			} 
			$msg = '[ ' . implode ( ', ' , $out ) . ']';
		}
		$line = "{$timePart} {$level} {$msg}";

		if (!@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '$file'. Is directory writable?");
		}
	}

	private $fileName;
	private $context;

	public function __construct( $fileName, $context=NULL )
	{
		$this->fileName = $fileName;
		if( $context==NULL ) {
			$this->context = getmypid();
		} else {
			$this->context = getmypid() . ';' . $context;
		}
	}

	public function setContext( $context )
	{
		$this->context = getmypid() . ';' . $context;
	}

	public function write( $level, $msg )
	{
		if( is_array($msg) ) {
			$out = array();
			foreach ($msg as $k => $v) { 
				$out[] = "$k=$v"; 
			} 
			$msg = '[ ' . implode ( ', ' , $out ) . ']';
		}

		if( $this->context!=NULL ) {
			$msg = "[{$this->context}] {$msg}";
		}
		self::log( $this->fileName, $level, $msg );
	}
}



