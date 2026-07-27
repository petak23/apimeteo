<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;

use function in_array;


/**
 * Zakladny presenter pre vsetky presentery v module API
 * 
 * Posledna zmena(last change): 27.07.2026
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.2
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

	//#[Persistent]
	//public $language = 'sk';

	public string $api_version = "2026-07-27";

	/** Pole s chybami pri uploade */
	public array $upload_error = [
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

		// Rozlíšenie prostredia podľa hostname
		$isLocalhost = in_array($httpRequest->getUrl()->getHost(), ['localhost', '127.0.0.1'], true);

		$allowedOrigins = $isLocalhost
				? ['http://localhost:5173']
				: ['https://vuemeteo.echo-msz.eu'];

		$origin = $httpRequest->getHeader('Origin');
		if ($origin && in_array($origin, $allowedOrigins, true)) {
				$httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
		} else {
				$httpResponse->setHeader('Access-Control-Allow-Origin', $allowedOrigins[0]);
		}
		$httpResponse->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
		$httpResponse->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
		$httpResponse->setHeader('Access-Control-Allow-Credentials', 'true');

		if ($httpRequest->getMethod() === 'OPTIONS') {
				$httpResponse->setCode(IResponse::S204_NoContent); // 204 = no content
				$this->terminate(); // okamžité ukončenie requestu
		}

		// Sprava uzivatela
		$user = $this->getUser(); //Nacitanie uzivatela

		// Kontrola ACL
		if (!($user->isAllowed($this->getName(), $this->action))) {
			if (!$this->getUser()->isLoggedIn()) {
				$this->sendJson([
						'status' => 401,
						'reason' => 'not_logged_in',
						'message' => 'Táto akcia je povolená len pre prihlásených. Prihláste sa, prosím!'
				]);
			} else {
				$this->sendJson([
						'status' => 403,
						'reason' => 'no_permission',
						'message' => 'Nemáte oprávnenie na vykonanie tejto akcie.'
				]);
			}
		}
	}

	public function beforeRender(): void
	{
		$this->template->api_version = $this->api_version;
	}
}
