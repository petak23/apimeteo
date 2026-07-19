<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Definícia pohľadu
 * Prevzaté z RatatoskrIoT
 * Posledna zmena 19.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class View
{
	use Nette\SmartObject;

	/** Popis pohledu */
	public string $name = "";
	public string $appName = "";
	public string $desc = "";

	/** Povoluje porovnavani, tj. vyber alternativniho roku? */
	public int $allowCompare = 0;
	/** Jednotlive polozky pohledu. Pole objektu ViewItem. */
	public array $items = [];
	/** Dalsi vlastnosti pohledu potrebne v Inventory */
	public string $token = "";
	public ?int $vorder = null;
	public ?string $render = null;
	public int $id = 0;

	public function __construct(
		string $name = "",
		string $desc = "",
		int $allowCompare = 0,
		string $appName = "",
		?string $render = null)
	{
		$this->appName = $appName;
		$this->name = $name;
		$this->desc = $desc;
		$this->allowCompare = $allowCompare;
		$this->render = $render;
	}
}
