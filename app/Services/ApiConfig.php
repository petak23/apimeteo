<?php

declare(strict_types=1);

namespace App\Services;

use Nette;

class ApiConfig
{
	use Nette\SmartObject;

	private $configs = [];

	public function __construct(
		$links,
		$appName,
		$dataRetentionDays,
		$minYear
	) {
		$this->configs = [
			"links" => $links,
			"appName" => $appName,
			"dataRetentionDays" => $dataRetentionDays,
			"minYear" => $minYear,
		];
	}

	public function getConfigs()
	{
		return $this->configs;
	}
}
