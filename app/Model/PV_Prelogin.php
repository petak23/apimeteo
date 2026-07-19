<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Utils;

/**
 * Model starajúci sa o tabuľku: prelogin
 * 
 * Posledna zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class PV_Prelogin extends Table 
{
	protected string $tableName = 'prelogin';
	
	public function createLoginaSession(int $deviceId, string $hash, string $key, string $remoteIp): int
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