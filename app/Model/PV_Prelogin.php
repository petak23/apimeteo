<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils;

/**
 * Model starajúci sa o tabuľku: prelogin
 * 
 * Posledna zmena 23.10.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class PV_Prelogin extends Table {

	/** @var string */
	protected $tableName = 'prelogin';
	
	public function createLoginaSession($deviceId, $hash, $key, $remoteIp)
	{
		$row = $this->add([
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => new Utils\DateTime,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		return $row->id;
	}
	

}