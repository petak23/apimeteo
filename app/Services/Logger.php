<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Utils\DateTime;

/**
 * Logger s denne rotovanými súbormi.
 * Last change 12.06.2026
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
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
 * Kontext sa pridáva ku všetkým ďalším ->write() až do okamžiku ->setContext();
 */
class Logger 
{
	use Nette\SmartObject;

	public const
		DEBUG = '-d-',
		INFO = '-i-',
		WARNING = 'WRN',
		ERROR = 'ERR';

	private string $fileName;
	private ?string $context;

	private static function convertToString( string|array $msg ): string
	{
		if( is_array($msg) ) {
			$out = [];
			foreach ($msg as $k => $v) { 
				$out[] = "$k=$v"; 
			} 
			return '[ ' . implode ( ', ' , $out ) . ']';
		}
		return $msg;
	}

	public static function log( string $fileName, string $level, string|array $msg ) 
	{
		$time = new DateTime();
		$namePart = $time->format('Y-m-d');
		$timePart = $time->format('H:i:s');
		$file = __DIR__ . '/../../log/' . $fileName . ".{$namePart}.txt";

		$msg = self::convertToString($msg);
		
		$line = "{$timePart} {$level} {$msg}";

		if (!@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException("Unable to write to log file '$file'. Is directory writable?");
		}
	}

	/**
	 * Summary of __construct
	 * @param string $fileName názov súboru bez prípony a dátumu, napr. "cron" pre log/cron.YYYY-MM-DD.txt
	 * @param mixed $context nepovinný kontext, ktorý sa bude pridávať ku každej správe, napr. "user1",
	 */
	public function __construct( string $fileName, ?string $context = null )
	{
		$this->fileName = $fileName;
		$this->context = getmypid() . ($context == null ? '' : ';' . $context);
	}

	public function setContext( string $context = '' ): void
	{
		$this->context = getmypid() . ';' . $context;
	}

	/**
	 * Ak je správa ako arraj tak ju spracuje a preloží na string $k=$v
	 * @param string $level
	 * @param string|array $msg
	 * @return void
	 */
	public function write( string $level, string|array $msg ): void
	{
		$msg = self::convertToString($msg);

		if( $this->context != null ) {
			$msg = "[{$this->context}] {$msg}";
		}
		self::log( $this->fileName, $level, $msg );
	}
}