<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette;
use Firebase\JWT\JWT; // https://github.com/firebase/php-jwt
use Nette\Application\UI\Presenter;

/**
 * Prezenter pre pristup k api prihlasovania a odhlasovania užívateľov.
 * Posledna zmena(last change): 26.11.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.0
 * @help 1.) https://forum.nette.org/cs/28370-data-z-post-request-body-reactjs-appka-se-po-ceste-do-php-ztrati
 */
class SignPresenter extends Presenter
{

	// -- Services
	/** @var Services\ApiConfig @inject */
	public $config;

	// -- DB
	/** @var Model\User_main @inject */
	public $user_main;

	/** Akcia pre prihlásenie */
	public function actionIn(): void
	{
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)
		$email = isset($_post['email']) ? $_post['email'] : "petak23@echo-msz.eu";
		$password = isset($_post['password']) ?	$_post['password'] : "Katka2810";

		try {
			$this->user->login($email, $password);

			$privateKey = openssl_pkey_get_private(
				file_get_contents(__DIR__ . '/../../ssl/private_key.pem'),
				$this->config->getPassPhase()
			);

			$user_data = $this->user_main->getUser(
				$this->user->getId(),
				$this->user,
				$this->template->baseUrl,
				true
			);
			// Payload data you want to include in the token
			$payload = [
				'user_id' => $user_data['id'],
				'email' => $user_data['email'],
				'exp' => time() + 7200, // Token expiration time (2 hour)
			];

			// Generate JWT token with private key
			$jwt = JWT::encode($payload, $privateKey, 'RS256');

			$httpResponse = $this->getHttpResponse();
			$httpResponse->addHeader('Access-Control-Allow-Origin', '*');
			$httpResponse->addHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT');
			//$httpResponse->addHeader('Access-Control-Allow-Headers', 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization');
			//$httpResponse->addHeader("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");*/
			$httpResponse->addHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
			$this->sendJson([
				'token' => $jwt,
				'user_data' => $user_data,
			]);
		} catch (Nette\Security\AuthenticationException $e) {
			$this->sendJson(['error' => 'Uživateľské meno alebo heslo je nesprávne!!!']);
		}
	}

	public function actionOut(): void
	{
		$this->user->logout(true);
	}
}
