<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;


/**
 * Zakladny presenter pre vsetky presentery v module API
 * 
 * Posledna zmena(last change): 29.07.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 */
abstract class BasePresenter extends Presenter
{

	// -- DB
	/** @var Model\User_main @inject */
	public $user_main;
	/** @var Model\User_permission @inject */
	public $user_permission;

	// -- Services
	/** @var Services\ApiConfig @inject */
	public $config;

	/** @persistent */
	public $language = 'sk';

	/** @var int Uroven registracie uzivatela  */
	public $id_reg;

	/** @var array - pole s chybami pri uploade */
	public $upload_error = [
		0 => "Bez chyby. Súbor úspešne nahraný.",
		1 => "Nahrávaný súbor je väčší ako systémom povolená hodnota!",
		2 => "Nahrávaný súbor je väčší ako je formulárom povolená hodnota!",
		3 => "Nahraný súbor bol nahraný len čiastočne...",
		4 => "Žiadny súbor nebol nahraný... Pravdepodobne ste vo formuláry žiaden nezvolili!",
		5 => "Upload error 5.",
		6 => "Chýbajúci dočasný priečinok!",
	];

	/** Vychodzie nastavenia */
	protected function startup(): void
	{
		parent::startup();

		$httpRequest = $this->getHttpRequest();
		$httpResponse = $this->getHttpResponse();

		// CORS hlavičky (rovnaké ako v index.php)
		$httpResponse->setHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
		$httpResponse->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
		$httpResponse->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
		$httpResponse->setHeader('Access-Control-Allow-Credentials', 'true');

		if ($httpRequest->getMethod() === 'OPTIONS') {
			$httpResponse->setCode(IResponse::S204_NO_CONTENT); // 204 = no content
			$this->terminate(); // okamžité ukončenie requestu
		}

		// Sprava uzivatela
		$user = $this->getUser(); //Nacitanie uzivatela
		// Kontrola prihlasenia a nacitania urovne registracie
		$this->id_reg = ($user->isLoggedIn()) ? $this->user_main->getUser($user->getId())->id_user_roles : 0;

		// Kontrola ACL
		if (!($user->isAllowed($this->name, $this->action))) {
			$this->sendJson(['status'=>405, 'message' => "Method not allowed!!!"]);
			//$this->error("Not allowed");
		}
	}

	public function beforeRender(): void
	{
		$this->template->appName = $this->config->getConfig('title');
		$this->template->links = $this->config->getConfig('links');
	}
}
