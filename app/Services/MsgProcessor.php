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

	// -- DB models
	
	/** @var Model\PV_Devices */
	public $pv_devices;

	/** @var Model\Measures */
	public $pv_measures;

	/** @var Model\PV_Sensors */
	public $pv_sensors;

	/** @var \App\Services\RaDataSource */
	public $datasource;

	public function __construct(\App\Services\RaDataSource $datasource, 
															Model\PV_Devices $pv_devices,
															Model\Measures $pv_measures,
															Model\PV_Sensors $pv_sensors,
														)
	{
		$this->datasource = $datasource;
		$this->pv_devices = $pv_devices;
		$this->pv_measures = $pv_measures;
		$this->pv_sensors = $pv_sensors;
	}

/******************** --------------------------------- PV - begin --------------------------------- ****************/
public function testSetUpTime($device_id, $d)
{
	$this->pv_devices->setUptime( $device_id, DateTime::createFromFormat('d.m.Y H:i:s', $d)->getTimestamp());
}

	/**
	 * Spracuje jeden request; ten ale môže obsahovať viacej správ.
	 * @var array $msgTotal = [<dátum a čas odoslania>, <dĺžka dát>, <data>, <uptime>]
	 * Formát dát ako pole:
	 * 	[<označenie senzora>, <formátovaná hodnota>, <raw hodnota>, <warning> ...]
	 */
	public function process_pv(Table\ActiveRow $sessionDevice, array $msgTotal, string $remoteIp, Logger $logger)
	{

		$logger->write(Logger::DEBUG, "MsgProcessor:process_pv => uptime:{$msgTotal[3]}, session device id:{$sessionDevice->device_id}");
		// Aktualizuj dobu prevádzky alebo bezporuchovosti vo formáte čísla - sekúnd
		$this->pv_devices->setUptime( $sessionDevice->device_id, (int)$msgTotal[3]); 
		
		foreach ($msgTotal[2] as $ds) {						// Spracujem data z jednotlivých senzorov
			$sensor = $this->pv_sensors->findOneBy(['device_id'=>$sessionDevice->device_id, 'name' => $ds['id']]); // Nájdenie príslušného senzora
					if ($sensor == null) { // Senzor neexistuje, vytvorenie nového
				$logger->write( Logger::INFO,  "MsgProcessor:process_pv => New channel definition" );
				$sensor = $this->processChannelDefinitionPV($sessionDevice, $ds);
			} 
			if ($sensor != null) { // Zapíšem dáta do kanála
				$logger->write( Logger::INFO,  "MsgProcessor:process_pv => dataof chanel={$ds['id']}" );
				$this->processDataPV($sessionDevice, $ds['raw_value'], $remoteIp, $sensor, $msgTotal[0], $logger);
			}
		}
	}

	/**
	 * Spracovanie jednej dátovej správy zo zariadenia
	 */
	public function processDataPV(
		Table\ActiveRow $sessionDevice, 
		string $value, 
		string $remoteIp, 
		//int $i, 
		Table\ActiveRow $sensor, 
		string $messageTime, 
		Logger $logger): void
	{

		if ($sensor->device_class != 3) { 
			// senzor DEVCLASS_CONTINUOUS_MINMAXAVG a DEVCLASS_CONTINUOUS
			$value_out = filter_var($value, FILTER_VALIDATE_FLOAT); // Zmeň data na float
			$logger->write(Logger::INFO,  "data: ch:{$sensor->channel_id} s:{$sensor->id} '{$value}' C-> {$value_out} @ ");
			$dataSession = '';
			$impCount = 0;
		} 
		//TODO: ***** Zatiaľ vypnuté *****
		/*else {
			// senzor DEVCLASS_IMPULSE_SUM
			// musíme počítať deltu v rámci aktuálnej session
			$fields = explode(';', $data, 2);
			if (count($fields) != 2) {
				throw new \Exception("Can't parse '{$data}' for dev {$sessionDevice->device_id}.");
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
			$logger->write(Logger::INFO,  "data: ch:{$sensor} s:{$sensor['id']} '{$data}' I({$prevVal})-> {$value_out} @ -{$timeDiff} s");
		}*/

		$sVal = $value_out;
		if ($sensor->preprocess_data == 1) {
			// prepočítavať data!
			$value_out *= $sensor->preprocess_factor;
		}
		
		$messageTime = DateTime::createFromFormat('d.m.Y H:i:s', $messageTime);

		$this->pv_measures->save(0, [
			'sensor_id' => $sensor->id,
			'data_time' => $messageTime,
			'server_time' => new DateTime,
			's_value' => $sVal,
			'session_id' => $sessionDevice->id,
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
	public function processChannelDefinitionPV(
		Table\ActiveRow $sessionDevice, 
		array $msgTotal, 
		//$remoteIp, 
		//$channel_id, 
		//Logger $logger
	): Table\ActiveRow|null
	{
		$sensor = $this->pv_sensors->findOneBy(['device_id' => $sessionDevice->device_id, 'name' => $msgTotal['id']]);

		if ($sensor == NULL) { // neexistuje, založenie	
			$sensor = $this->pv_sensors->add([
				'device_id' => $sessionDevice->device_id,
				//'channel_id' => $channel_id,
				'name' => $msgTotal['id'],
				'device_class' => $msgTotal['id_device_classes'],
				'id_value_types' => $msgTotal['id_value_types'],
				'msg_rate' => isset($msgTotal['msg_rate']) ? $msgTotal['msg_rate'] : 3600,	// Ak nie je nastavené nastav predvolenú hodnotu
				'preprocess_data' => ($msgTotal['preprocess_factor'] === NULL) ? 0 : 1,
				'preprocess_factor' => $msgTotal['preprocess_factor'],
			]);
		} /*else {
			// existuje
			if ($sensor->channel_id != $channel_id) {
				// existuje, ale ma zlý channel_id -> nastaviť
				$sensor = $this->pv_sensors->oprav( $sensor->id, ['channel_id' => $channel_id] );
			}
		}

		// a nastaviť NULL na channel_id na ostatných záznamoch rovnakého zariadenia s rovnakým channel_id
		$this->pv_sensors->findBy([
			'device_id' => $sessionDevice->device_id,
			'channel_id' => $channel_id,
			'name <>' => $msgTotal['id']
			])->update(['channel_id' => null]);*/
		return $sensor;
	}
//******************** --------------------------------- PV - end --------------------------------- ****************/

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
	 * Spracuje jeden request; ten ale môže obsahovať viacej správ.
	 */
	public function process(Model\SessionDevice $sessionDevice, string $msgTotal, ?string $remoteIp, Logger $logger)
	{
		$logData = bin2hex($msgTotal);
		//D/ $logger->write( Logger::INFO, "msg {$logData}");

		// payload send timestamp
		$sendTime = (ord($msgTotal[0]) << 16) | (ord($msgTotal[1]) << 8) | ord($msgTotal[2]);
		$logger->write(Logger::DEBUG, "uptime:{$sendTime}");
		$this->pv_devices->setUptime($sessionDevice->device_id, $sendTime);

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

/**
	 * Spracovanie jednej dátovej správy zo zariadenia
	 */
	public function processData(Model\SessionDevice $sessionDevice, $msg, ?string $remoteIp, int $i, int $channel, $timeDiff, Logger $logger)
	{
		/*$sensor = $this->pv_sensors->getSensorByChannel($sessionDevice->device_id, $channel);
		if ($sensor == NULL) {
			throw new \Exception("Ch {$channel} not found for dev {$sessionDevice->device_id}.");
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
				throw new \Exception("Can't parse '{$data}' for dev {$sessionDevice->device_id}.");
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

}
