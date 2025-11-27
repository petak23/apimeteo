<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use App\Services\Logger;
use Nette\Application\AbortException;
use Nette\Http;
use Nette\Utils;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Throwable;
use Tracy\Debugger;

/**
 * Presenter pre komunikáciu api s perifériami.
 * Posledná zmena(last change): 28.08.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2025 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.2
 */
class CommPresenter extends BasePresenter
{
	/** @var Services\MsgProcessor @inject */
	public $msgProcessor;
	
	// -- DB
	/** @var Model\PV_Sessions @inject */
	public $pv_sessions;
	/** @var Model\PV_Devices @inject */
	public $pv_devices;

	public function actionDefault() : void 
	{
		//$this->msgProcessor->testSetUpTime(1, '24.11.2025 14:22:01');
		$this->sendJson(['status'=>200, 'message'=>'Testovacia akcia.']);		
	}

	/**
	 * Formát data správy:
	 *      {
	 *          "device_name": "<názov zariadenia>",
	 *          "login_time": "<dátum a čas prihlásenia>",
	 *          "appname": "<názov aplikácie>",
	 *          "payload_hash": "<SHA256 z device_name + masterPassword + login_time + appname>"
	 *      }
	 * Result:
	 *      200 - OK
	 *      400 - other error
	*/
	public function actionLogin() : void {
		Debugger::enable( Debugger::Production );
		$logger = new Logger( 'pv-conn' );

		try {
				
			$httpRequest = $this->getHttpRequest();

			$remoteIp = $httpRequest->getRemoteAddress(); 
			$logger->setContext("D");

			$postMessage = $httpRequest->getRawBody(); // Ulož príchodziu správu zisti jej veľkosť a zaloguj
			$postSize = strlen( $postMessage );
			$logger->write( Logger::INFO, "data+ {$postSize}b {$remoteIp}");
			$logger->write( Logger::INFO, "[{$postMessage}]" );

			try {
				$json_msg = Utils\Json::decode($postMessage, forceArrays: true);
				$logger->write( Logger::INFO, "JSON decoded.");
			} catch (Utils\JsonException $e) {
				throw new \Exception("Bad request (1). Incorect JSON format of incoming data!!!");
			}

			$device = $this->pv_devices->getDeviceBy(['name' => $json_msg["device_name"]]);
			if (is_array($device) && isset($device["status"]) && $device["status"] == 404) {
				throw new \Exception("Device {$json_msg['device_name']} not found!");
			} else {
				$logger->write( Logger::INFO, "Device found ID: {$device->attrs->id}" );
			}

			$control_hash = hash('sha256', $json_msg["device_name"] . $this->config->getConfig('masterPassword') . $json_msg["login_time"] . $json_msg["appname"]);
			if( $control_hash !== $json_msg["comm_key"]  ) {
				throw new \Exception("Not valid sha256 of message! Expected: {$control_hash}, Given: {$json_msg['comm_key']}");
			}

			// zalozit session
			$sessionHash = Random::generate(8, '0-9A-Za-z');
			$sessionId = $this->pv_sessions->createLoginSession( $device->attrs->id, $sessionHash, $control_hash,	$remoteIp );

		} catch (\Exception $e) {
			$logger->write( Logger::ERROR,  "ERR(comm-login-main): " . get_class($e) . ": " . $e->getMessage() );
			
			$httpResponse = $this->getHttpResponse();
			$httpResponse->setCode(Http\IResponse::S400_BAD_REQUEST );
			$this->sendJson(['status' => 400, 'message' => "ERR {$e->getMessage()}"]);
			$this->terminate();
		}

		$logger->write( Logger::INFO, "login-OK D:{$device->attrs->id} S:{$sessionId}" );
		$this->sendJson(['status' => 200, 'device_id' => $device->attrs->id, 'session_id' => $sessionId, 'session_hash' => $sessionHash]);
	}

	/**
	 * Formát data správy:
	 *      {
	 *          "session_id": "<id session>",
	 *          "session_hash": "<hash session>",
	 *          "last_measure": "<dátum a čas odoslania>",
	 *          "data_length": <dĺžka dát>,
	 *          "data_string": "<data>",
	 *          "data_message": "<last_measure>;<data_length>;<data_string>",
	 *          "payload_hash": "<SHA256 z data_message>"
	 *      }
	 * Result:
	 *      200 - OK
	 *      400 - other error
	 * 
	 * [{
	 * "sensors":[{
	 * 		"id":"te",
	 * 		"value":"22.35 °C",
	 * 		"raw_value":"22.35",
	 * 		"id_device_classes":1,
	 * 		"id_value_types":1,
	 * 		"preprocess_factor":null,
	 * 		"warning":"success"
	 * 	}, ... ],
	 * 	"last_measure":"25.11.2025 11:41:00",
	 * 	"priority":1,
	 * 	"data_length":5,
	 * 	"data_string":":::::",
	 * 	"data_message":";5;:::::Ka5t_Qu1646",
	 * 	"payload_hash":"e6c2117a5593b89ba3f2a0573f644693e43a1ad86c85b5e4de4c6bb3ac42237d",
	 * 	"session_id":887,
	 * 	"session_hash":"6UN7lOmD",
	 * 	"rssi":-61,
	 * 	"uptime":"71456128",
	 * 	"appname":"MeteoZahradka v.1.1.3 (24.11.2025)",
	 * 	"device_name":"PV:meteozahradka"}]
	 * 
	*/

	public function actionDatajson(): void
	{
		Debugger::enable( Debugger::Production );
		$logger = new Logger( 'pv-conn' );

		try {
				
			$httpRequest = $this->getHttpRequest();

			$remoteIp = $httpRequest->getRemoteAddress(); 
			$logger->setContext("D");

			$postMessage = $httpRequest->getRawBody(); // Ulož príchodziu správu zisti jej veľkosť a zaloguj
			$postSize = strlen( $postMessage );
			$logger->write( Logger::INFO, "data+ {$postSize}b {$remoteIp}");
			$logger->write( Logger::INFO, "[{$postMessage}]" );

			try {
				$json_msg = Utils\Json::decode($postMessage, forceArrays: true);
				$logger->write( Logger::INFO, "JSON decoded in actionDatajson: ". $postMessage );
			} catch (Utils\JsonException $e) {
				throw new \Exception("Bad request (1). Incorect JSON format of incoming data!!!");
			}
			
			//$session = Strings::trim($msg_parts[0]); 
			if( !isset($json_msg["session_id"]) || 
					!isset($json_msg["session_hash"]) || 
					Strings::length( $json_msg["session_hash"] ) == 0  
				) {
				throw new \Exception("Empty session ID.");
			} 

			$sessionDevice = $this->pv_sessions->checkSessionPV( $json_msg["session_id"], $json_msg["session_hash"] ); // Over session id voči session hash
			$logger->setContext("D;D:{$sessionDevice->device_id}");
			$str_message = $json_msg["last_measure"] .";". (string)$json_msg["data_length"] .";". $json_msg["data_string"] .$this->config->getConfig('masterPassword');
			$control_hash = hash('sha256', $json_msg["data_message"]);
			if( $control_hash !== $json_msg["payload_hash"]  ) {
				throw new \Exception("Not valid sha256 of message! " . $json_msg["data_message"]);
			}

			if( strlen($json_msg["data_string"]) !== (int)$json_msg["data_length"]  ) {
				throw new \Exception("Incorrect data length!");
			}
			
			/*
			Aktuálny formát:
			[0] - dátum a čas odoslania = $json_msg["last_measure"]
			[1] - dĺžka dát = $json_msg["data_length"]
			[2] - data = $json_msg["sensors"]
			*/
			$this->msgProcessor->process_pv( $sessionDevice, [ $json_msg["last_measure"], $json_msg["data_length"], $json_msg["sensors"] ], $remoteIp, $logger );  

			$logger->write( Logger::INFO, "OK");

			$this->sendJson(['status' => 200, 'message' => 'OK']);
			//$this->terminate();
				
		} catch (Throwable $e) {
			if ($e instanceof AbortException) {
					throw $e;
			}
			$logger->write( Logger::ERROR,  "CommPresenter:actionDatajson:Ex=> ERR: " . get_class($e) . "(".$e->getCode()."): " . $e->getMessage() );
			
			$httpResponse = $this->getHttpResponse();
			$httpResponse->setCode($e->getCode() != 0 ? $e->getCode() : Http\IResponse::S400_BAD_REQUEST );
			$this->sendJson(['status' => $e->getCode() != 0 ? $e->getCode() : 400, 'message' => "CommPresenter:actionDatajson:Ex=> ERR {$e->getMessage()}"]);
			$this->terminate();
		}
	}



	/**
	 * Formát data správy:
	 *      <session>;<SHA256 z payloadu>;<dátum a čas odoslania>;<dĺžka dát>;<data>
	 * Formát session:
	 * 			<session_id>:<session_hash>
	 * Formát dát: (označenie senzora je jedinečná hodnota)
	 * 			<označenie senzora>:<hodnota>;<označenie senzora>:<hodnota>;... - ak je viac posielaných hodnôt, tak sú oddelené ";"  
	 * Result:
	 *      200 - OK
	 *      400 - other error
	*/
	/*public function actionData(): void
	{
		Debugger::enable( Debugger::Production );
		$logger = new Logger( 'pv-conn' );

		try {
				
			$httpRequest = $this->getHttpRequest();

			$remoteIp = $httpRequest->getRemoteAddress(); 
			$logger->setContext("D");

			$postMessage = $httpRequest->getRawBody(); // Ulož príchodziu správu zisti jej veľkosť a zaloguj
			$postSize = strlen( $postMessage );
			$logger->write( Logger::INFO, "data+ {$postSize}b {$remoteIp}");
			$logger->write( Logger::INFO, "[{$postMessage}]" );

			$msg_parts = explode( ";", $postMessage, 5 );	// Rozdeľ vstupnú správu podľa ";" na 5 častí a skontroluj
			if( count($msg_parts) < 5 ) {
				throw new \Exception("Bad request (2). Message is too short! Number of parts: " . count($msg_parts) . ". Required 5!!!");                
			}
			/*
			$msg_parts[0] - session
			$msg_parts[1] - SHA256 z payloadu
			$msg_parts[2] - dátum a čas odoslania 
			$msg_parts[3] - dĺžka dát
			$msg_parts[4] - data
			* /
			$session = Strings::trim($msg_parts[0]); 
			if( Strings::length( $session ) == 0  ) {
				throw new \Exception("Empty session ID.");
			} 
			
			$sessionData = explode( ":", $session, 2 );
			if( count($sessionData) != 2 ) { // Musí to byť presne 2 <session_id> a <session_hash>
				throw new \Exception("Bad request (3). Not valid session data. Must be: <session_id>:<session_hash>");                
			}
			$logger->write( Logger::INFO, "S:{$sessionData[0]}"); 
			$sessionDevice = $this->pv_sessions->checkSession( $sessionData[0], $sessionData[1] ); // Over session id voči session hash
			$logger->setContext("D;D:{$sessionDevice->deviceId}");
			
			array_shift($msg_parts); // Vypustí prvý prvok poľa teda <session>
			/*
			$msg_parts[0] - SHA256 z payloadu
			$msg_parts[1] - dátum a čas odoslania 
			$msg_parts[2] - dĺžka dát
			$msg_parts[3] - data
			* /
			// TODO vloženie hash hesla z údajov
			$control_hash = hash('sha256', $msg_parts[1] .";". $msg_parts[2] .";". $msg_parts[3] ."taJne687*+WX_-heslo");
			if( $control_hash !== $msg_parts[0]  ) {
				throw new \Exception("Not valid sha256 of message!");
			}

			if( strlen($msg_parts[3]) !== (int)$msg_parts[2]  ) {
				throw new \Exception("Incorrect data length!");
			}
			
			array_shift($msg_parts); // Vypustí prvý prvok poľa teda <SHA256 z payloadu>
			/*
			Aktuálny formát:
			$msg_parts[0] - dátum a čas odoslania 
			$msg_parts[1] - dĺžka dát
			$msg_parts[2] - data
			* /
			$this->msgProcessor->process_pv( $sessionDevice, $msg_parts, $remoteIp, $logger );  

			$logger->write( Logger::INFO, "OK");

			$this->sendJson(['status' => 200, 'message' => 'OK']);
				
		} catch (\Exception $e) {
			$logger->write( Logger::ERROR,  "ERR: " . get_class($e) . ": " . $e->getMessage() );
			
			$httpResponse = $this->getHttpResponse();
			$httpResponse->setCode(Http\IResponse::S400_BAD_REQUEST );
			$this->sendJson(['status' => 400, 'message' => "ERR {$e->getMessage()}"]);
			$this->terminate();
		}
	}*/




}
