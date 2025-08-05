<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model starajuci sa o tabulku prelogin
 * 
 * Posledna zmena 27.06.2022
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2022 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class PV_Prelogin extends Table {
	/** @var string */
	protected $tableName = 'prelogin';
	
	public function createLoginaSession($deviceId, $hash, $key, $remoteIp)
	{
		$row = $this->pridaj([
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => new \Nette\Utils\DateTime,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		return $row->id;
	}
	

}