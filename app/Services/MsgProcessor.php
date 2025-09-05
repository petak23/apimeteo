<?php

declare(strict_types=1);

namespace App\Services;

use App\Model;
use App\Services\Logger;
use Nette;
use Nette\Database\Table;
use DateTime;

class MsgProcessor
{
	use Nette\SmartObject;

	// -- DB
	/** @var Model\PV_Sensors @inject */
	public $pv_senors;
	/** @var Model\PV_Devices @inject */
	public $pv_devices;
	/** @var Model\Measures @inject */
	public $pv_measures;
	/** @var Model\PV_Sensors @inject */
	public $pv_sensors;

	/** @var \App\Services\RaDataSource */
	public $datasource;

	public function __construct(\App\Services\RaDataSource $datasource)
	{
		$this->datasource = $datasource;
	}


	/*
	b0 - deviceClass
	b1 - valueType
	b2 b3 b4 msgRate
	b5 - deviceName len - !!! správne má byť sensorName len
	b6... - device name - NO \0 at end - !!! správne má byť sensor name
	*/
	/**
	 * Spracovanie definície kanálu
	 */
	public function processChannelDefinition(Model\SessionDevice $sessionDevice, $msg, $remoteIp, $i, Logger $logger)
	{
		$devClass = ord($msg[$i++]);
		$valueType = ord($msg[$i++]);
		$msgRate = (ord($msg[$i]) << 16) | (ord($msg[$i + 1]) << 8) | ord($msg[$i + 2]);
		$i += 3;
		$channel = ord($msg[$i++]);
		$nameLen = ord($msg[$i++]);
		$name = substr($msg, $i, $nameLen);

		$factor = NULL;

		$c = strpos($name, "|");
		if ($c === FALSE) {
			// nerobíme nič
		} else {
			$factor = substr($name, $c + 1);
			$name = substr($name, 0, $c);
		}

		$logger->write(Logger::INFO,  "ChDef ch:{$channel} class:{$devClass} valType:{$valueType} rate:{$msgRate} factor:{$factor} '{$name}'");

		$this->datasource->processChannelDefinition($sessionDevice, $channel, $devClass, $valueType, $msgRate, $name, $factor);
	}


	/**
	 * Spracovanie jednej dátovej správy zo zariadenia
	 */
	public function processData(Model\SessionDevice $sessionDevice, $msg, ?string $remoteIp, int $i, int $channel, $timeDiff, Logger $logger)
	{
		/*$sensor = $this->pv_senors->getSensorByChannel($sessionDevice->deviceId, $channel);
		if ($sensor == NULL) {
			throw new \Exception("Ch {$channel} not found for dev {$sessionDevice->deviceId}.");
		}

		$data = substr($msg, $i);

		if ($sensor['id_device_classes'] != 3) {
			// senzor DEVCLASS_CONTINUOUS_MINMAXAVG a DEVCLASS_CONTINUOUS
			// s datami nič nerobíme
			$value_out = filter_var($data, FILTER_VALIDATE_FLOAT); // Zmeň data na float
			$logger->write(Logger::INFO,  "data: ch:{$channel} s:{$sensor['id']} '{$data}' C-> {$value_out} @ -{$timeDiff} s");
			$dataSession = '';
			$impCount = 0;
		} else {
			// senzor DEVCLASS_IMPULSE_SUM
			// musíme počítať deltu v rámci aktuálnej session
			$fields = explode(';', $data, 2);
			if (count($fields) != 2) {
				throw new \Exception("Can't parse '{$data}' for dev {$sessionDevice->deviceId}.");
			}
			$impCount = intval($fields[0]);
			$prevVal = 'X';
			if (
				$sensor['data_session'] != NULL && strcmp($sensor['data_session'], $fields[1]) == 0
			) {
				// ide o data v rámci aktuálnej session; teda meriame rozdiel od posledného získaného
				if ($sensor['imp_count'] > $impCount) {
					// nejaké divné, že by sa náhodovu vygenerovalo rovnaké číslo session?
					$value_out = $impCount;
					$prevVal = "!{$sensor['imp_count']}!";
				} else {
					$value_out = $impCount - $sensor['imp_count'];
					$prevVal = $sensor['imp_count'];
				}
			} else {
				// nova session = začíname od nuly
				$value_out = $impCount;
			}
			$dataSession = $fields[1];
			$logger->write(Logger::INFO,  "data: ch:{$channel} s:{$sensor['id']} '{$data}' I({$prevVal})-> {$value_out} @ -{$timeDiff} s");
		}

		$sVal = $value_out;
		if ($sensor->preprocess_data == 1) {
			// prepočítavať data!
			$value_out *= $sensor->preprocess_factor;
		}

		$this->pv_measures->save(0, [
			'sensor_id' => $sensor->id,
			'data_time' => $messageTime,
			'server_time' => new DateTime,
			's_value' => $sVal,
			'session_id' => $sessionDevice->sessionId,
			'remote_ip' => $remoteIp,
			'out_value' => $value_out
		]);

		$values = [];
		$values['last_data_time'] = $messageTime;
		if ($sensor['device_class'] != 3) {
			$values['last_out_value'] = $value_out;
		}
		if ($dataSession != '') {
			$values['imp_count'] = $impCount;
			$values['data_session'] = $dataSession;
		}
		$this->pv_sensors->findBy(['id' => $sensor->id, '(last_data_time IS NULL) OR (last_data_time < ?)' => $messageTime])->update($values);*/
	}


	/**
	 * Spracuje jeden request; ten ale môže obsahovať viacej správ.
	 */
	public function process(Model\SessionDevice $sessionDevice, string $msgTotal, ?string $remoteIp, Logger $logger)
	{
		$logData = bin2hex($msgTotal);
		//D/ $logger->write( Logger::INFO, "msg {$logData}");

		// payload send timestamp
		$sendTime = (ord($msgTotal[0]) << 16) | (ord($msgTotal[1]) << 8) | ord($msgTotal[2]);
		$logger->write(Logger::DEBUG, "uptime:{$sendTime}");
		$this->pv_devices->setUptime($sessionDevice->deviceId, $sendTime);

		// telemetry payload header
		$j = 3;

		while (true) {

			//---- iterace ďalšej správy v dátovom bloku
			$msgLen = @ord($msgTotal[$j]);
			//D/ $logger->write( Logger::INFO, "  pos={$j}, len={$msgLen}");
			if ($msgLen == 0) {
				break;
			}
			$msg = substr($msgTotal, $j + 1, $msgLen);
			$j += 1 + $msgLen;

			//---- spracovanie jednej správy
			$i = 0;
			$channel = ord($msg[$i++]);
			$msgTime = (ord($msg[$i]) << 16) | (ord($msg[$i + 1]) << 8) | ord($msg[$i + 2]);
			$i += 3;

			$timeDiff = $sendTime - $msgTime;
			//D/ $logger->write( Logger::INFO,  "msg ch:{$channel} time:-{$timeDiff}" );

			if ($channel == 0) {
				//D $logger->write( Logger::INFO,  "channel definition" );
				$this->processChannelDefinition($sessionDevice, $msg, $remoteIp, $i, $logger);
			} else {
				//D $logger->write( Logger::INFO,  "data" );
				$this->processData($sessionDevice, $msg, $remoteIp, $i, $channel, $timeDiff, $logger);
			}
		}
	}
/******************** --------------------------------- PV - begin --------------------------------- ****************/
	/**
	 * Spracuje jeden request; ten ale môže obsahovať viacej správ.
	 * @var array $msgTotal = [<dátum a čas odoslania>, <dĺžka dát>, <data>]
	 * Formát dát ako pole:
	 * 	[<označenie senzora>, <formátovaná hodnota>, <raw hodnota>, <warning> ...]
	 */
	public function process_pv(Table\ActiveRow $sessionDevice, array $msgTotal, string $remoteIp, Logger $logger)
	{

		$logger->write(Logger::DEBUG, "uptime:{$msgTotal[0]}");
		$this->pv_devices->setUptime( $sessionDevice->deviceId, $msgTotal[0]); // Aktualizuj dobu prevádzky alebo bezporuchovosti vo formáte čísla - sekúnd

		foreach ($msgTotal[2] as $key => $ds) {						// Spracujem data z jednotlivých senzorov
			//list($sensor_name, $value) = explode(":", $ds); 		// Rozložíme "<označenie senzora>:<hodnota>"
			$channel = $this->pv_senors->findOneBy(['name' => $ds['id']]); // Nájdenie príslušného senzora

			if ($channel == null) {
				$logger->write( Logger::INFO,  "new channel definition" );
				$this->processChannelDefinitionPV($sessionDevice, $msgTotal, $remoteIp, $key, $logger);
				throw new \Exception("Channel not found...", 1);
			} else {
				//D $logger->write( Logger::INFO,  "data" );
				$this->processDataPV($sessionDevice, $ds['raw_value'], $remoteIp, $key, $channel->id, $msgTotal[0], $logger);
			}
		}
	}

	/**
	 * Spracovanie jednej dátovej správy zo zariadenia
	 */
	public function processDataPV(Table\ActiveRow $sessionDevice, string $value, string $remoteIp, int $i, int $channel, string $messageTime, Logger $logger)
	{
		$sensor = $this->pv_senors->getSensorByChannel($sessionDevice->deviceId, $channel);
		if ($sensor == NULL) {
			throw new \Exception("Ch {$channel} not found for dev {$sessionDevice->deviceId}.");
		}

		//$data = substr($msg, $i);

		if ($sensor->id_device_classes != 3) { 
			// senzor DEVCLASS_CONTINUOUS_MINMAXAVG a DEVCLASS_CONTINUOUS
			$value_out = filter_var($value, FILTER_VALIDATE_FLOAT); // Zmeň data na float
			$logger->write(Logger::INFO,  "data: ch:{$channel} s:{$sensor->id} '{$value}' C-> {$value_out} @ ");
			$dataSession = '';
			$impCount = 0;
		} 
		// ***** Zatiaľ *****
		/*else {
			// senzor DEVCLASS_IMPULSE_SUM
			// musíme počítať deltu v rámci aktuálnej session
			$fields = explode(';', $data, 2);
			if (count($fields) != 2) {
				throw new \Exception("Can't parse '{$data}' for dev {$sessionDevice->deviceId}.");
			}
			$impCount = intval($fields[0]);
			$prevVal = 'X';
			if (
				$sensor['data_session'] != NULL && strcmp($sensor['data_session'], $fields[1]) == 0
			) {
				// ide o data v rámci aktuálnej session; teda meriame rozdiel od posledného získaného
				if ($sensor['imp_count'] > $impCount) {
					// nejaké divné, že by sa náhodovu vygenerovalo rovnaké číslo session?
					$value_out = $impCount;
					$prevVal = "!{$sensor['imp_count']}!";
				} else {
					$value_out = $impCount - $sensor['imp_count'];
					$prevVal = $sensor['imp_count'];
				}
			} else {
				// nova session = začíname od nuly
				$value_out = $impCount;
			}
			$dataSession = $fields[1];
			$logger->write(Logger::INFO,  "data: ch:{$channel} s:{$sensor['id']} '{$data}' I({$prevVal})-> {$value_out} @ -{$timeDiff} s");
		}*/

		$sVal = $value_out;
		if ($sensor->preprocess_data == 1) {
			// prepočítavať data!
			$value_out *= $sensor->preprocess_factor;
		}
		
		$this->pv_measures->save(0, [
			'sensor_id' => $sensor->id,
			'data_time' => $messageTime,
			'server_time' => new DateTime,
			's_value' => $sVal,
			'session_id' => $sessionDevice->sessionId,
			'remote_ip' => $remoteIp,
			'out_value' => $value_out
		]);

		$values = [];
		$values['last_data_time'] = $messageTime;
		if ($sensor['device_class'] != 3) {
			$values['last_out_value'] = $value_out;
		}
		if ($dataSession != '') {
			$values['imp_count'] = $impCount;
			$values['data_session'] = $dataSession;
		}
		$this->pv_sensors->findBy(['id' => $sensor->id, '(last_data_time IS NULL) OR (last_data_time < ?)' => $messageTime])->update($values);
	}

	/**
	 * Spracovanie definície kanálu
	 */
	public function processChannelDefinitionPV(Table\ActiveRow $sessionDevice, array $msgTotal, $remoteIp, $channel_id, Logger $logger)
	{
		
		$sensor = $this->pv_senors->findOneBy(['device_id' => $sessionDevice->deviceId, 'name' => $msgTotal['device_name']]);

		if ($sensor == NULL) { // neexistuje, založenie	
			$this->pv_senors->insert([
				'device_id' => $sessionDevice->deviceId,
				'channel_id' => $channel_id,
				'name' => $msgTotal['device_name'],
				'id_device_classes' => $msgTotal['id_device_classes'],
				'id_value_types' => $msgTotal['id_value_types'],
				'msg_rate' => $msgTotal['msg_rate'],
				'preprocess_data' => ($msgTotal['preprocess_factor'] === NULL) ? 0 : 1,
				'preprocess_factor' => $msgTotal['preprocess_factor'],
			]);
		} else {
			// existuje
			if ($sensor->channel_id != $channel_id) {
				// existuje, ale ma zlý channel_id -> nastaviť
				$this->pv_senors->update( $sensor->id, ['channel_id' => $channel_id] );
			}
		}

		// a nastaviť NULL na channel_id na ostatných záznamoch rovnakého zariadenia s rovnakým channel_id
		$this->pv_senors->where([
			'device_id' => $sessionDevice->deviceId,
			'channel_id' => $channel_id,
			'name <>' => $msgTotal['device_name']
			])->update(['channel_id' => null]);
	}
//******************** --------------------------------- PV - end --------------------------------- ****************/

}