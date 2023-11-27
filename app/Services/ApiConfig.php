<?php

declare(strict_types=1);

namespace App\Services;

use Latte\Compiler\Nodes\Php\Scalar\NullNode;
use Nette;

class ApiConfig
{
	use Nette\SmartObject;

	private $configs = [];

	private $passphrase = null;

	public function __construct(
		$links,
		$appName,
		$dataRetentionDays,
		$minYear,
		$passphrase
	) {
		$this->configs = [
			"links" => $links,
			"appName" => $appName,
			"dataRetentionDays" => $dataRetentionDays,
			"minYear" => $minYear,
		];
		$this->passphrase = $passphrase;
	}

	public function getConfigs(): array
	{
		return $this->configs;
	}

	public function getConfig(String $name): String|int|array|null
	{
		return isset($this->configs[$name]) ? $this->configs[$name] : null;
	}

	public function getPassPhase(): ?String
	{
		return $this->passphrase;
	}
}
