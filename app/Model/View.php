<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Definice pohledu
 * Prevzaté z RatatoskrIoT
 */
class View
{
	use Nette\SmartObject;

	/*
	 * Popis pohledu
	 */
	/** @var string */
	public $name = "";
	/** @var string */
	public $appName = "";
	/** @var string */
	public $desc = "";

	/**
	 * Povoluje porovnavani, tj. vyber alternativniho roku?
	 */
	public $allowCompare = 0;

	/**
	 * Jednotlive polozky pohledu.
	 * Pole objektu ViewItem.
	 * @var array
	 */
	public $items = [];

	/*
	 * Dalsi vlastnosti pohledu potrebne v Inventory
	 */
	/** @var string */
	public $token = "";
	
	public $vorder = null;
	
	public $render = null;
	
	/** @var int */
	public $id = 0;

	public function __construct(
		string $name = "",
		string $desc = "",
		int $allowCompare = 0,
		string $appName = "",
		$render = null)
	{
		$this->appName = $appName;
		$this->name = $name;
		$this->desc = $desc;
		$this->allowCompare = $allowCompare;
		$this->render = $render;
	}
}
