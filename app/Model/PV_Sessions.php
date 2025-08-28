<?php

namespace App\Model;

use App\Exceptions\NoSessionException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

/**
 * Model, ktory sa stara o tabulku sessions
 * 
 * Posledna zmena 28.08.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class PV_Sessions extends Table
{

  /** @var string */
  protected $tableName = 'sessions';

	public function createLoginSession(int $deviceId, String $hash, String $key, String $remoteIp): int
	{
		$row = $this->add([
			'hash' => $hash,
			'device_id' => $deviceId,
			'started' => new DateTime,
			'session_key' => $key,
			'remote_ip' => $remoteIp
		]);

		return $row->id;
	}

  public function deleteSession(int $id): void
  {
    $this->findBy(["device_id" => $id])->delete();
  }

  public function getSessionById(int $sessionId) : ActiveRow|null 
  {
    return $this->find($sessionId);
  }

	/**
	 * Check session validity. 
	 * Throws NoSessionException when session is not valid. 
	 */
	public function checkSessionPV(int $sessionId, string $sessionHash): ActiveRow|null
	{
		$session = $this->find($sessionId);
		if ($session == null) {
			throw new NoSessionException("Session {$sessionId} not found.");
		}

		if (strcmp($session->hash, $sessionHash) != 0) {
			throw new NoSessionException("Bad hash");
		}

		$now = new DateTime;
		// Životnosť session 1 deň
		if ($now->diff($session->started)->days > 0) {
			throw new NoSessionException("session expired");
		}

		return $session;
	}

  /**
	 * Check session validity. 
	 * Throws NoSessionException when session is not valid. 
	 * @deprecated Use checkSessionPV() instead
	 */
	public function checkSession(int $sessionId, string $sessionHash): SessionDevice
	{
		$session = $this->find($sessionId);
		if ($session == null) {
			throw new NoSessionException("Session {$sessionId} not found.");
		}

		if (strcmp($session->hash, $sessionHash) != 0) {
			throw new NoSessionException("Bad hash");
		}

		$now = new DateTime;
		// Životnosť session 1 deň
		if ($now->diff($session->started)->days > 0) {
			throw new NoSessionException("session expired");
		}

		$rc = new SessionDevice();
		$rc->sessionId = $sessionId;
		$rc->sessionKey = $session->session_key;
		$rc->deviceId = $session->device_id;
    
		return $rc;
	}
}
