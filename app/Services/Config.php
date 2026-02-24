<?php

declare(strict_types=1);

namespace App\Services;

use Nette;

class Config
{
	use Nette\SmartObject;

	private $masterPassword;
	public $minYear = 2020;

	public function __construct( $masterPassword, int $minYear = 2020) {
		$this->masterPassword = $masterPassword;
		$this->minYear = $minYear;
	}


	private function getMasterKey()
	{
		return hash("sha256", $this->masterPassword . 'RatatoskrIoT', true);
	}

	public function encrypt($data, $fieldName): string
	{
		$aesIV = substr(hash("sha256", $fieldName, true), 0, 16);
		$aesKey = $this->getMasterKey();
		$encrypted = openssl_encrypt($data, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
		if ($encrypted === FALSE) {
			Logger::log('webapp', Logger::ERROR, "nelze zasifrovat");
		}
		return bin2hex($encrypted);
	}

	public function decrypt($data, $fieldName): string
	{
		$aesIV = substr(hash("sha256", $fieldName, true), 0, 16);
		$aesKey = $this->getMasterKey();

		$decrypted = openssl_decrypt(hex2bin($data), 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
		//dumpe($decrypted, $data, $aesIV, $aesKey, $fieldName);
		if ($decrypted === false) {
			Logger::log('webapp', Logger::ERROR, "nelze desifrovat");
			return "";
		}
		return $decrypted;
	}
}
