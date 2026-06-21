<?php
declare(strict_types=1);
/**
 * Last change 12.06.2026
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 *
 */
namespace App\Presenters;

use App\Model;
use App\Services;
use App\Services\Logger;
use Nette;
use Nette\Database\Table;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Image;

final class CrontaskPresenter extends BasePresenter
{
	use Nette\SmartObject;

	public const NAME = 'cron';

	/** Doba drzeni beznych logu, dny  */
	public const LOG_RETENTION_BASE = 7;
	/** Doba drzeni audit logu, dny */
	public const LOG_RETENTION_AUDIT = 31;

	/** Kolik radku se nacte z DB pro zacatek zpracovani  */
	public $batchSize = 4000;
	/** Maximalni delka behu jednoho tasku v sekundach */
	public $maxRunTime1 = 40;
	/** Maximalni delka behu jednoho tasku v sekundach */
	public $maxRunTime2 = 15;
	/** Maximalni delka behu jednoho tasku v sekundach */
	public $maxRunTime3 = 25;

	/**
	 * Nutny konec prace
	 */
	public $endTime;

	public $startTime;
	public $processedBatches = 0;
	public $processedRecords = 0;


	/** @var Services\CrontaskDataSource */
	private $datasource;

	private Services\MailService $mailService;

	public $config;

	/** @var Model\PV_Sensors @inject */
	public $sensors;

	/** @var Model\PV_Notifications @inject */
	public $notifications;

	/**
	 * @param Services\CrontaskDataSource $datasource
	 * @param Services\MailService $mailsv
	 * @param Services\Config $cfg
	 */
	public function __construct(
		Services\CrontaskDataSource $datasource,
		Services\MailService $mailsv,
		Services\Config $cfg
	) {
		$this->datasource = $datasource;
		$this->mailService = $mailsv;
		$this->config = $cfg;
	}

	/**
	 * Task spúšťaný každú hodinu; beží maximálne minútu.
	 * 
	 * Provadi akce:
	 * - zkontroluje stav senzoru a nafrontuje pozadavky na notifikacni maily:
	 *      - prekroceni min/max limitu
	 *      - neprichazejici data
	 * - odesle notifikacni maily, pokud nejake jsou
	 * - smaze stare zaznamy v 'prelogin' a pokud jsou, aktualizuje odpovidajici zaznamy v 'devices'
	 * - zpracuje 'measures' 
	 *      - vygeneruje z novych zaznamu hodinova 'sumdata' 
	 *      - vygeneruje ze zmenenych hodinovych 'sumdata' denni 'sumdata'
	 * - projde nove obrazky (bloby s typem 'jpg' a nazvem 'camera' a otaguje ty, co jsou cerne)
	 * 
	 * O 0-tej hodine sa spúšťa task raz denne; beží neobmezene.
	 * 
	 * Denné tasky:
	 * - zmaže odoslané notifikácie z tabuľky notifications (staršie ako 14 dní)
	 * - zmaže staré data
	 *      - measures
	 *      - sumdata
	 *      - bloby (db aj súbory)
	 * - zmaže staré logy
	 */
	public function renderDefault(): void
	{
		
		if (!$this->checkIp()) {
			$this->setView('notvalidip');
			return;
		}

		$this->template->ip_not_allowed = false;
		$this->template->batches = 0;
		$this->template->records = 0;
		$this->template->time = 0;

		$logger = new Logger("cron");
		$resultMsg = "";
		$hourNow = (new DateTime())->format('H');

		try {
			$totalEnde = time() + $this->maxRunTime2  + $this->maxRunTime1;
			$this->startTime = time();
			$this->endTime = time() + $this->maxRunTime1;

			$resultMsg .= $this->checkSensors();
			$resultMsg .= $this->sendNotificationMails($logger);
			
			$resultMsg .= $this->processPrelogin($logger);
			$resultMsg .= $this->processMeasures($logger);

			$this->startTime = time();
			$this->endTime = time() + $this->maxRunTime2;
			$this->processSumdata($logger);

			// Necháme na obrázky zbytok do celkového maxima dĺžky behu
			$this->endTime = time() + $this->maxRunTime3;
			if ($this->endTime >  $totalEnde) {
				$this->endTime = $totalEnde;
			}
			//$this->processImages($logger); // TODO: ešte som nepozrel, či to bude fungovať...
			$resultMsg = $resultMsg . "\n✅  Hour {$hourNow} OK\n";
		} catch (\Exception $e) {
			$logger->write(Logger::ERROR,  "ERR: " . get_class($e) . ": " . $e->getMessage());
			$resultMsg = $resultMsg . "\n❌  Hour {$hourNow} ERROR: " . get_class($e) . ": " . $e->getMessage(). "\n";
		}

		if ($hourNow == '00') { // denný tasky spúšťame o 0-tej hodine
			$logger->setContext("daily");
			try {
				$this->notifications->deleteNotifications();
				$this->deleteData($logger);
				$this->deleteLogs($logger);

				$resultMsg = $resultMsg . "\n✅  Daily OK";
				$logger->write(Logger::INFO, "Done.");
			} catch (\Exception $e) {
				Logger::log(self::NAME, Logger::ERROR,  "ERR: " . get_class($e) . ": " . $e->getMessage());
				$resultMsg = $resultMsg . "\n❌  Daily ERROR: " . get_class($e) . ": " . $e->getMessage(). "\n";
			}
			$logger->setContext();
		}

		$this->template->resultMsg = $resultMsg;
	}

	public $maxRunTimeExport = 55;

	/**
	 * Export novych measures do externiho systemu.
	 * Viz popis https://pebrou.wordpress.com/2021/01/19/ratatoskriot-replikace-dat-do-jineho-systemu/
	 * 
	 */
	// TODO - skontrolovať
	public function renderExport()
	{

		if (!$this->checkIp()) {
			$this->setView('notvalidip');
			return;
		}

		$this->template->ip_not_allowed = false;
		$this->template->batches = 0;
		$this->template->records = 0;
		$this->template->time = 0;

		$timeLimit = time() + $this->maxRunTimeExport;

		$logger = new Logger("cron");
		$logger->setContext("exp");

		try {

			$ct = 0;

			$exporter = $this->context->getService('exportPlugin');

			while (true) {
				$rows = $this->datasource->getExportData();
				if (count($rows) < 1) {
					break;
				}
				foreach ($rows as $row) {
					$rc = $exporter->exportRecord(
						$row->id,
						$row->data_time,
						$row->server_time,
						$row->value,
						$row->sensor_id,
						$row->sensor_name,
						$row->device_id,
						$row->device_name,
						$row->user_id
					);
					if ($rc == 0) {
						$this->datasource->rowExported($row->id);
						$ct++;
					} else {
						$logger->write(Logger::DEBUG,  "#{$row->id} time={$row->data_time} value={$row->value} sensor={$row->sensor_id}={$row->sensor_name} device={$row->device_id}={$row->device_name} user={$row->user_id}");
						$logger->write(Logger::WARNING, "Chyba exportu #{$rc}.");
						$logger->write(Logger::INFO, "Stopping, {$ct} records done.");
						$this->template->result = "ERROR #{$rc}";
						return;
					}
					if (time() > $timeLimit) {
						// prekrocena maximalni delka behu
						break;
					}
				}
				if (time() > $timeLimit) {
					// prekrocena maximalni delka behu
					break;
				}
			}

			$this->template->result = "OK";
			$logger->write(Logger::INFO, "Done, {$ct} records.");
		} catch (\Exception $e) {
			$logger->write(Logger::ERROR,  "ERR: " . get_class($e) . ": " . $e->getMessage());
		}
	}

/* ----------------------------------------------------------------- */

	/**
	 * Zkontroluje prekroceni min/max limitu
	 */
	private function checkMinMaxLimits(Table\ActiveRow $sensor, float $value_out): array
	{
		$out = [
			'warn_max_fired' 	=> null,
			'warn_max_send'		=> 0,
			'warn_min_fired' 	=> null,
			'warn_min_send'		=> 0,
			'store_warnings'	=> 0,
		];

		if (isset($sensor->warn_max) && $sensor->warn_max) { // mame hlidat maximum
			if ($value_out >= $sensor->warn_max_val) { // prekrocene maximum
				// zacatek udalosti
				if (!$sensor->warn_max_fired) { // pokud to nemame zapsane
					$out['warn_max_fired'] = $sensor->last_data_time;
					$out['warn_max_send'] = 0;
					$out['store_warnings'] = 1;
					Logger::log(self::NAME,  Logger::INFO,  "MAX reached: {$sensor->id} [{$sensor->device->name}:{$sensor->name}] {$value_out} >= {$sensor->warn_max_val}");
				}

				// poslani notifikace
				if ($out['warn_max_fired'] && $out['warn_max_send'] == 0) {
					// casova vzdalenost!
					$utime = (DateTime::from($out['warn_max_fired']))->getTimestamp();
					if (time() - $utime > $sensor->warn_max_after) {
						$out['warn_max_send'] = 1;
						$out['store_warnings'] = 1;
						$this->notifications->insert($sensor->device_id, $sensor->id, 1, $sensor->warn_max_text, $value_out, $out['warn_max_fired']);
						Logger::log(self::NAME,  Logger::INFO,  "MAX notification: {$sensor->id} [{$sensor->device->name}:{$sensor->name}] {$value_out} >= {$sensor->warn_max_val} delay={$sensor->warn_max_after} s");
					}
				}
			} else if ($value_out < $sensor->warn_max_val_off) { // jsme OK pod vypinacim limitem
				if ($sensor->warn_max_fired) { // ale mame znacku, ze jsme nad => smazat
					$out['warn_max_fired'] = null;
					$out['store_warnings'] = 1;
					$this->notifications->insert($sensor->device_id, $sensor->id, -1, $sensor->warn_max_text, $value_out, $sensor->last_data_time);
					Logger::log(self::NAME,  Logger::INFO,  "MAX cleared {$sensor->id} [{$sensor->device->name}:{$sensor->name}] ");
				}
			}
		}

		if (isset($sensor->warn_min) && $sensor->warn_min) { // mame hlidat minimum
			if ($value_out <= $sensor->warn_min_val) { // prekrocene minimum

				// zacatek udalosti
				if (!$sensor->warn_min_fired) { // pokud to nemame zapsane
					$out['warn_min_fired'] = $sensor->last_data_time;
					$out['warn_min_send'] = 0;
					$out['store_warnings'] = 1;
					Logger::log(self::NAME, Logger::INFO,  "MIN reached: {$sensor->id} [{$sensor->device->name}:{$sensor->name}] {$value_out} <= {$sensor->warn_min_val}");
				}

				// poslani notifikace
				if ($out['warn_min_fired'] && $out['warn_min_send'] == 0) {
					// casova vzdalenost!
					$utime = (DateTime::from($out['warn_min_fired']))->getTimestamp();
					if (time() - $utime > $sensor->warn_min_after) {
						$out['warn_min_send'] = 1;
						$out['store_warnings'] = 1;
						$this->notifications->insert($sensor->device_id, $sensor->id, 2, $sensor->warn_min_text, $value_out, $out['warn_min_fired']);
						Logger::log(self::NAME,  Logger::INFO,  "MIN notification: {$sensor->id} [{$sensor->device->name}:{$sensor->name}] {$value_out} <= {$sensor->warn_min_val} delay={$sensor->warn_min_after} s");
					}
				}
			} else if ($value_out > $sensor->warn_min_val_off) { // jsme OK nad limitem
				if ($sensor->warn_min_fired) { // ale mame znacku, ze jsme pod => smazat
					$out['warn_min_fired'] = null;
					$out['store_warnings'] = 1;
					$this->notifications->insert($sensor->device_id, $sensor->id, -2, $sensor->warn_min_text, $value_out, $sensor->last_data_time);
					Logger::log(self::NAME, Logger::INFO,  "MIN cleared {$sensor->id} [{$sensor->device->name}:{$sensor->name}] ");
				}
			}
		}

		return $out;
	}

	/**
	 * Zkontroluje neaktivitu
	 */
	private function checkLastDataTs(Table\ActiveRow $sensor): array
	{
		$out = [
			'warn_noaction_fired' 	=> null,
			'warn_max_send'		=> 0,
			'warn_min_fired' 	=> null,
			'warn_min_send'		=> 0,
			'store_warnings'	=> 0,
		];

		$utime = (DateTime::from($sensor->last_data_time))->getTimestamp();
		if (time() - $utime > $sensor->msg_rate) { // nechodi zpravy
			if (!$sensor->warn_noaction_fired) { // nemame to zapsane
				$out['warn_noaction_fired'] = new DateTime();
				$out['store_warnings'] = 1;
				$this->notifications->insert($sensor->device_id, $sensor->id, 4, "{$sensor->last_data_time}", 0, new DateTime());
				Logger::log(self::NAME,  Logger::INFO,  "Notification NO_DATA: {$sensor->id} [{$sensor->device->name}:{$sensor->name}] ");
			}
		} else { // zpravy chodi
			if ($sensor->warn_noaction_fired) { // ale mame znacku, ze nechodi => smazat
				$out['warn_noaction_fired'] = null;
				$out['store_warnings'] = 1;
				$this->notifications->insert($sensor->device_id, $sensor->id, -4, "{$sensor->last_data_time}", 0, new DateTime());
				Logger::log(self::NAME,  Logger::INFO,  "Notification NO_DATA cleared {$sensor->id} [{$sensor->device->name}:{$sensor->name}] ");
			}
		}

		return $out;
	}

	/**
	 * Zkontroluje stav senzoru a pripravi notifikace, pokud jsou nejake ve spatnem stavu
	 */
	private function checkSensors(): string
	{
		$rows = $this->datasource->getSensors();
		foreach ($rows as $sensor) {

			$zapisWarningy = 0;
			$out = [];
			$value_out = isset($sensor->last_out_value) ? $sensor->last_out_value : null;
			
			if ($value_out != null) {
				$out = array_merge($out, $this->checkMinMaxLimits($sensor, $value_out));
				$zapisWarningy += $out['store_warnings'];
				unset($out['store_warnings']);
			}
			
			if ($sensor->last_data_time && $sensor->device->monitoring) {
				$out = array_merge($out, $this->checkLastDataTs($sensor));
				$zapisWarningy += $out['store_warnings'];
				unset($out['store_warnings']);
			}

			if ($zapisWarningy) {	
				$this->datasource->updateSensorsWarnings($out);
			}
		}
		return "✅  Checked " . count($rows) . " sensors with " . $zapisWarningy . " warnings.\n";
	}

	/**
	 * Zpracuje cekajici notifikace
	 */
	private function sendNotificationMails(Logger $logger): string
	{
		$rows = $this->notifications->getNotifications();
		foreach ($rows as $row) {
			$type = abs($row->event_type);
			$prefix = $row->event_type > 0 ? "VAROVANIE: " : "Koniec poplachu: ";
			$subject = " - {$row->devices->name}:{$row->sensor->name}";
			$text = "Zariadenie: <b>{$row->devices->name}</b> ({$row->devices->desc})
								<br>Senzor: <b>{$row->sensor->name}</b> ({$row->sensor->desc})";
			$ps = $prefix . " " . $subject;
			if ($type == 1) {
				$subject = "Hodnota príliš vysoká" . $subject;
				$text =
					"<p>{$ps}</p>
					<p><b>{$row->custom_text}<b></p>
					<p>
						Hodnota: <b>{$row->out_value} {$row->unit}</b>
						<br>{$text}
						<br>Čas: <b>{$row->event_ts}</b>
					</p>
					";
			} else if ($type == 2) {
				$subject = "Hodnota príliš nízka" . $subject;
				$text =
					"<p>{$ps}</p>
					<p><b>{$row->custom_text}<b></p>
					<p>
						Hodnota: <b>{$row->out_value} {$row->unit}</b>
						<br>{$text}
						<br>Čas: <b>{$row->event_ts}</b>
					</p>
					";
			} else if ($type == 4) {
				$subject = "Zo senzora neprichádzajú dáta" . $subject;
				$text =
					"<p>{$ps}</p>
					<p>
						{$text}
						<br>Posledné dáta: <b>{$row->custom_text}<b>
						<br>Aktuálny čas: <b>{$row->event_ts}</b>
					</p>
					";
			}

			$logger->write(Logger::INFO, "Notifikace #{$row->id} '{$ps}' pre {$row->user_main->email}");

			$this->mailService->sendMail(	$row->user_main->email,	$ps, $text);

			$this->notifications->close($row->id);
		}
		return "✅  Poslaných " . count($rows) . " notifikácií.\n";
	}


	private function shouldExit(): bool
	{
		return (time() > $this->endTime);
	}


	/**
	 * Prejde všetky záznamy za danú hodinu a 
	 * - pre záznamy, kde nie je status=1:
	 *      - nastaví status=1
	 * - spočíta hodinové priemery/sumy a uloží ich do tabuľky, ak to pre daný senzor má robiť
	 */
	private function processSensorHour(int $sensorId, string $date, string $hour, Logger $logger)
	{
		$logger->write(Logger::DEBUG,  "Measures for sensor=$sensorId; $date $hour");

		$min = NULL;
		$min_time = NULL;
		$max = NULL;
		$max_time = NULL;
		$avg = NULL;
		$sum = 0;

		$sensor = $this->sensors->getSensor($sensorId);
		if (!$sensor) {
			$logger->write(Logger::ERROR, "Nenájdený senzor {$sensorId}!");
			return;
		}
		$device_classes = $sensor->id_device_classes;
		$rows = $this->datasource->getRecordsForSensorHour($sensorId, $date, $hour);
		foreach ($rows as $rec) {
			//D/ Logger::log( self::NAME, Logger::DEBUG,  $rec );

			if ($device_classes == 1 || $device_classes == 4) {
				// maji se pocitat prumery hodnot
				if ($min === NULL) {
					// prvni zaznam
					$min = $rec->out_value;
					$min_time = $rec->data_time;
					$max = $rec->out_value;
					$max_time = $rec->data_time;
					if ($device_classes == 4) {
						$sum = $rec->out_value;
					}
				} else {
					if ($min > $rec->out_value) {
						$min = $rec->out_value;
						$min_time = $rec->data_time;
					}
					if ($max < $rec->out_value) {
						$max = $rec->out_value;
						$max_time = $rec->data_time;
					}
					if ($device_classes == 4) {
						$sum += $rec->out_value;
					}
				}
			} else if ($device_classes == 3) {
				// ma se pocitat sumarizace hodnot
				$sum += $rec->out_value;
			}

			if ($rec->status == 0) {
				// novy zaznam, musi byt upraven
				$this->datasource->updateMeasure($rec->id);
				$this->processedRecords++;
			}
		}

		// spocten min,max -> udelame si stred (to se tyka jen hodinovych zaznamu, u dennich se pocita jinak!)
		if ($device_classes == 1) {
			$avg = ($min + $max) / 2;
		}

		// pokud se nejedna o class 2, kde se nepocitaji sumarizace
		if ($device_classes != 2) {
			// vsechny zaznamy zpracovany, je treba vytvorit sumarni zaznam
			$this->datasource->createSummary(
				$sensorId,
				$date,
				$hour,
				$min,
				$min_time,
				$max,
				$max_time,
				$avg,
				$sum,
				1,
				0
			);
		}
	}


	/**
	 * Zpracovava zdrojova data a pocita z nich hodinove sumarizace
	 */
	private function processMeasures(Logger $logger): string
	{
		$records = $this->datasource->getRecordsForProcessing($this->batchSize);

		$currentSensor = false;
		$currentDate = false;
		$currentHour = false;

		foreach ($records as $rec) {

			//D/ Logger::log( self::NAME, Logger::DEBUG,  $rec );
			if ($this->shouldExit()) {
				break;
			}

			$date = $rec->data_time->format('Y-m-d');
			$hour = $rec->data_time->format('H');
			if ($currentSensor != $rec->sensor_id || $currentDate != $date || $currentHour != $hour) {
				$currentSensor = $rec->sensor_id;
				$currentDate = $date;
				$currentHour = $hour;

				// zpracujeme danou hodinu a dany senzor 
				$this->processSensorHour($currentSensor, $currentDate, $currentHour, $logger);

				$this->processedBatches++;
			}
			// vsechny ostatni zaznamy ze stejne hodiny a senzoru v poli preskocime, protoze ty uz jsme zpracovali
		}

		$time = time() - $this->startTime;

		$logger->write(Logger::INFO,  "Measures: done. {$this->processedBatches} batches, {$this->processedRecords} records in {$time} sec");
		return "✅  Measures: done. {$this->processedBatches} batches, {$this->processedRecords} records in {$time} sec.\n";
	}


	/**
	 * Prejde všetky sumarizácie za daný deň a 
	 * - pre záznamy, kde nie je status=1:
	 *      - nastaví status=1
	 * - spočíta  priemery a uloží ich do tabuľky
	 */
	private function processSensorSummary(int $sensorId, string $date, Logger $logger)
	{
		$logger->write(Logger::DEBUG,  "Summary for sensor=$sensorId; $date");

		$min = NULL;
		$min_time = NULL;
		$max = NULL;
		$max_time = NULL;
		$avg = NULL;
		$count = 0;
		$sum = 0;

		$val0 = NULL;
		$val6 = NULL;
		$val12 = NULL;
		$val18 = NULL;


		$sensor = $this->sensors->getSensor($sensorId);
		$device_classes = $sensor->id_device_classes;
		$rows = $this->datasource->getSumsForSensorDay($sensorId, $date);
		foreach ($rows as $rec) {
			//D/ Logger::log( self::NAME, Logger::DEBUG,  (array)$rec );

			$count++;

			//  id	sensor_id	sum_type	rec_date	rec_hour	min_val	min_time	max_val	max_time	avg_val	sum_val	status
			if ($device_classes == 1 || $device_classes == 4) {
				// maji se pocitat prumery hodnot
				if ($min === NULL) {
					// prvni zaznam
					$min = $rec->min_val;
					$min_time = $rec->min_time;
					$max = $rec->max_val;
					$max_time = $rec->max_time;
					if ($device_classes == 4) {
						$sum = $rec->sum_val;
					}
				} else {
					if ($min > $rec->min_val) {
						$min = $rec->min_val;
						$min_time = $rec->min_time;
					}
					if ($max < $rec->max_val) {
						$max = $rec->max_val;
						$max_time = $rec->max_time;
					}
					if ($device_classes == 4) {
						$sum += $rec->sum_val;
					}
				}

				if ($rec->rec_hour == 0) {
					$val0 = $rec->avg_val;
				} else if ($rec->rec_hour == 6) {
					$val6 = $rec->avg_val;
				} else if ($rec->rec_hour == 12) {
					$val12 = $rec->avg_val;
				} else if ($rec->rec_hour == 18) {
					$val18 = $rec->avg_val;
				}
			} else if ($device_classes == 3) {
				// ma se pocitat sumarizace hodnot
				$sum += $rec->sum_val;
			}

			if ($rec->status == 0) {
				// novy zaznam, musi byt upraven
				$this->datasource->updateSumdata($rec->id);
				$this->processedRecords++;
			}
		}

		// spocist prumer, pokud mame data
		if ($device_classes == 1) {
			if ($val0 != NULL && $val6 != NULL && $val12 != NULL && $val18 != NULL) {
				$avg = ($val0 + $val6 + $val12 + $val18) / 4;
			}
		}

		// Všetky záznamy sú spracované, je treba vytvoriť sumárny záznam
		$this->datasource->createSummary(
			$sensorId,
			$date,
			-1,
			$min,
			$min_time,
			$max,
			$max_time,
			$avg,
			$sum,
			2,
			$count
		);

		if ($device_classes == 3 || $device_classes == 4) {
			// Pre impulzné senzory dá celkovú dennú sumu do sensor['last_out_value']
			$this->datasource->updateSensorValue($sensorId, $sum);
		}
	}

	/**
	 * Spracúvava hodinové sumarizácie a počíta z nich denné sumy
	 */
	private function processSumdata(Logger $logger): void
	{
		$this->processedBatches = 0;
		$this->processedRecords = 0;

		$records = $this->datasource->getSumsForProcessing($this->batchSize);

		$currentSensor = false;
		$currentDate = false;

		foreach ($records as $rec) {

			//D/ Logger::log( self::NAME, Logger::DEBUG, (array)$rec );
			if ($this->shouldExit()) {
				break;
			}

			$date = $rec->rec_date->format('Y-m-d');
			if ($currentSensor != $rec->sensor_id || $currentDate != $date) {
				$currentSensor = $rec->sensor_id;
				$currentDate = $date;

				// Spracujeme danú hodinu a daný senzor 
				$this->processSensorSummary($currentSensor, $currentDate, $logger);

				$this->processedBatches++;
			}
			// Všetky ostatné záznamy z rovnakej hodiny a senzoru v poli preskočíme, pretože tie už sme spracovali
		}

		$this->template->batches = $this->processedBatches;
		$this->template->records = $this->processedRecords;
		$this->template->time = time() - $this->startTime;

		$logger->write(Logger::INFO,  "Summary: done. {$this->processedBatches} batches, {$this->processedRecords} records in {$this->template->time} sec");
	}

	const RESIZE_X = 150;
	const RESIZE_Y = 150;
	const COLOR_THRESHOLD1 = 70;
	const COLOR_THRESHOLD2 = 150;
	const COUNT_THRESHOLD = 6;

	/**
	 * 0 = black
	 * 1 = non-black
	 * 2 = black+lamp
	 */
	private function testImage($filename, $logger)
	{
		$logger->write(Logger::DEBUG, "  $filename");
		$count = 0;

		try {

			$image = Image::fromFile($filename);
			$image->resize(self::RESIZE_X, self::RESIZE_Y, Image::STRETCH);

			for ($x = 0; $x < self::RESIZE_X; $x++) {
				for ($y = 0; $y < self::RESIZE_Y; $y++) {
					$rgb = $image->colorAt($x, $y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					if (
						($r > self::COLOR_THRESHOLD1 || $g > self::COLOR_THRESHOLD1 || $b > self::COLOR_THRESHOLD1)
						&&
						(($r + $g + $b) > self::COLOR_THRESHOLD2)
					) {
						$count++;
						if ($count > self::COUNT_THRESHOLD) {
							$logger->write(Logger::DEBUG, "    at $x,$y : $r,$g,$b");
							return 1;
						}
					}
				}
			}

			if ($count == 0) {
				$logger->write(Logger::DEBUG, "    black");
				return 0;
			} else {
				$logger->write(Logger::DEBUG, "    black+lamp {$count} px");
				return 2;
			}
		} catch (\Nette\Utils\ImageException $e) {
			$logger->write(Logger::ERROR,  "ERR: " . get_class($e) . ": " . $e->getMessage());
			return 1;
		}
	}


	private function processImages($logger)
	{
		$ctImages = 0;
		$ctBlack = 0;

		$images = $this->datasource->getImagesForProcessing();
		foreach ($images as $image) {

			if ($this->shouldExit()) {
				break;
			}

			$logger->write(Logger::DEBUG, "img {$image['id']}");
			$file = FileSystem::normalizePath(__DIR__ . "/../../data/" . $image['filename']);
			$out = $this->testImage($file, $logger);
			$ctImages++;
			if ($out == 0) {
				$this->datasource->updateImageAll($image['id'], $image['description'] . ' BLACK');
				$ctBlack++;
			} else if ($out == 1) {
				$this->datasource->updateImageStatus($image['id']);
			} else {
				$this->datasource->updateImageAll($image['id'], $image['description'] . ' BLACK LAMP');
				$ctBlack++;
			}
		}

		$logger->write(Logger::INFO,  "Images: done. $ctImages images, $ctBlack black.");
	}


	private function processPrelogin(Logger $logger): string
	{
		$limit = (new DateTime())->modify('-3 min');
		$rows = $this->datasource->getOldPrelogins($limit);
		foreach ($rows as $row) {
			$logger->write(Logger::INFO, "Unused prelogin for dev {$row['device_id']} from IP {$row['remote_ip']}");
			$this->datasource->markDeviceLoginProblem($row['device_id'], $row['started']);
			$this->datasource->deletePrelogin($row['id']);
		}
		return "✅  Prelogin cleanup completed. (" . count($rows) . ") \n";
	}


	private function checkIp(): bool
	{
		$remoteIp = $this->getHttpRequest()->getRemoteAddress();
		foreach ($this->datasource->getCronAllowed() as $ip) {
			if (strcmp($ip, $remoteIp) == 0) {
				$this->template->ip_not_allowed = false;
				return true;
			}
		}
		Logger::log(self::NAME,  Logger::WARNING,  "Crontask fired from {$remoteIp}, not allowed.");
		$this->template->ip_not_allowed = true;
		return false;
	}

	private function deleteData(Logger $logger): void
	{
		$users = $this->datasource->getAllUserSettings();
		foreach ($users as $user) {
			//  id, username, measures_retention, sumdata_retention, blob_retention
			$logger->write(Logger::INFO, "#{$user['id']} {$user['username']} measures:{$user['measures_retention']} sumdata:{$user['sumdata_retention']} blobs:{$user['blob_retention']}");
			// per-uzivatel
			$sensors = $this->datasource->getSensorIdsForUser($user['id']);
			if (count($sensors) > 0) {
				// smazat measures
				if ($user['measures_retention'] > 0) {
					$purgeDate = new DateTime();
					$retention = $user['measures_retention'] + 15;
					$purgeDate->modify("- {$retention} day");
					$ct = $this->datasource->deleteMeasures($sensors, $purgeDate);
					$logger->write(Logger::INFO, "  measures older than {$purgeDate}: {$ct}");
				}

				// smazat sumdata
				if ($user['sumdata_retention'] > 0) {
					$purgeDate = new DateTime();
					$retention = $user['sumdata_retention'] + 15;
					$purgeDate->modify("- {$retention} day");
					$ct = $this->datasource->deleteSumdata($sensors, $purgeDate);
					$logger->write(Logger::INFO, "  sumdata older than {$purgeDate}: {$ct}");
				}
			}

			// mazat blobs + soubory
			if ($user['blob_retention'] > 0) {
				$purgeDate = new DateTime();
				$retention = $user['blob_retention'] + 1;
				$purgeDate->modify("- {$retention} day");
				$blobs = $this->datasource->getBlobsForUser($user['id'], $purgeDate);
				foreach ($blobs as $blob) {
					$logger->write(Logger::INFO, "  blob {$blob['id']}; {$blob['data_time']}; {$blob['filename']}");
					$file = FileSystem::normalizePath(__DIR__ . "/../../data/" . $blob['filename']);
					$logger->write(Logger::INFO, "    {$file}");
					FileSystem::delete($file);
					if (1 != $this->datasource->deleteBlob($blob['id'])) {
						$logger->write(Logger::WARNING, '    nelze smazat z DB?');
					}
				}
			}
		}
	}

	private function deleteLogs(Logger $logger)
	{
		$dir = FileSystem::normalizePath(__DIR__ . "/../../log/");

		$logger->write(Logger::INFO, 'Deleting base logs older than ' . self::LOG_RETENTION_BASE . ' days:');
		foreach (Finder::findFiles('*.txt', '*.html')
			->exclude('audit*')
			->date('<', '- ' . self::LOG_RETENTION_BASE . ' days')
			->in($dir)
			as $key => $file) {
			$logger->write(Logger::DEBUG, "  {$file->getPathname()}");
			FileSystem::delete($file->getPathname());
		}

		$logger->write(Logger::INFO, 'Deleting audit logs older than ' . self::LOG_RETENTION_AUDIT . ' days:');
		foreach (Finder::findFiles('audit*.txt')
			->date('<', '- ' . self::LOG_RETENTION_AUDIT . ' days')
			->in($dir)
			as $key => $file) {
			$logger->write(Logger::DEBUG, "  {$file->getPathname()}");
			FileSystem::delete($file->getPathname());
		}

		$logger->write(Logger::INFO, 'Logs done.');
	}
}