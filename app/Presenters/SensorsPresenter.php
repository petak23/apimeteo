<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette\Database;
use function sprintf;

/**
 * Prezenter pre pristup k api senzorov.
 * Posledna zmena(last change): 20.07.2026
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.9
 */
class SensorsPresenter extends BasePresenter
{

	// -- DB
	/** @var Model\PV_Devices @inject */
	public $devices;
	/** @var Model\PV_Sensors @inject */
	public $sensors;
	/** @var Model\Measures @inject */
	public $measures;

	/** @var Services\Config */
	public $config;

	private Services\ChartDataSource $datasource;

	public function __construct(Services\Config $config, Services\ChartDataSource $datasource) {
		$this->config = $config;
		$this->datasource = $datasource;
	}

	/** Vráti zoznam senzorov pre dané zariadenie */
	public function actionSensors(int $id): void
	{
		$d = $this->devices->getDevice($id, true, true);
		$this->sendJson($d["sensors"]);
	}

	public function actionSensor(int $id): void
	{
		$sensor = $this->sensors->getAndCheckSensorAccess($id);
		$this->sendJson($sensor);
	}

	public function actionMeasures(int $id): void
	{
		$this->sendJson($this->measures->getMeasures($id));
	}

	public function actionMeasureslast(int $id): void
	{
		$this->sendJson($this->measures->getLastMeasure($id));
	}

	/**
	 * Používa sa len na vykreslenie automaticky pripraveného grafu volaného z detailu zariadenia.
	 */
	private function getViewSourceId(int $device_class, int $lenDays): int
	{
		if ($device_class == 1) {
			// CONTINUOUS_MINMAXAVG
			$vsId = 1; // Automatická data
		} else if ($device_class == 2) {
			// CONTINUOUS
			$vsId = 5; // Detailní data
		} else {
			// IMPULSE_SUM
			if ($lenDays > 30) {
				$vsId = 6;  // denni suma
			} else {
				$vsId = 7;  // hodinova suma
			}
		}
		return $vsId;
	}

	/**
	 * Vykresľovanie štatistík pre senzor - volané z administrácie, autentizovaný používateľ.
	 */
	public function actionSensorstat(
		int $id,
		string $dateFrom = '',
		int $lenDays = 7,
		?int $altYear = null,
		string $plus = "",
		string $minus = "",
		string $altplus = "",
		string $altminus = "",
		string $current = "",
		string $currentweek = "",
		string $currentmonth = "",
		string $currentyear = "",
		string $plusMon = "",
		string $minusMon = "",
		string $plusYear = "",
		string $minusYear = "",
		string $currentday = ""
	) {

		$sensor_data = $this->sensors->getAndCheckSensorAccess($id);
		if ($sensor_data['status'] != 200) {
			$this->sendJson($sensor_data);
			return;
		}
		$sensor = $sensor_data['sensor'];

		$params = new Model\ChartParameters(
			$dateFrom,
			$lenDays,
			$altYear,
			$plus,
			$minus,
			$altplus,
			$altminus,
			$current,
			$currentweek,
			$currentmonth,
			$currentyear,
			$plusMon,
			$minusMon,
			$plusYear,
			$minusYear,
			$currentday,

			$this->config->minYear
		);
		//dumpe($params);

		$params->allowCompare();
		
		$chart = new Model\Chart(null);
		
		
		$device = [
			'name' => $sensor['dev_name'],
			'desc' => $sensor['dev_desc']
		];
		
		$my_devices[$sensor['device_id']] = $device;

		$out = [
			'status' => 200,
			'allowCompare' => TRUE,
			'id' => $id,
			'dateFrom' => $params->dateTimeFrom->format('Y-m-d'),
			'lenDays' => $params->lenDays,
			'altYear' => $params->altYear,
			
			'chW' => $chart->width(),
			'chH' => $chart->height(),
			// šírka stĺpca pre vykreslenie - obrázok + malá rezerva
			'maxW' => $chart->width() + 85,

			'sensor' => $sensor,

			'source1' => true,
			'isKompozit' => false,

			'isChart' => true,

			'measureStats' => $this->datasource->getMeasuresStats($id),
			'sumdataStats' => $this->datasource->getSumdataStats($id),
			'sumdataCount' => $this->datasource->getSumdataCount($id),

			'devices' => $my_devices,
			'years' => $params->getAltYearsList(),
		];

		$viewSource = $this->datasource->getViewSource($this->getViewSourceId($sensor['device_class'], $params->lenDays));
		
		$outView = [];
		$vi = [
			'sensor_ids' => $id,
			'axis' => 1,
			'name' => $sensor['desc'],
			'sensor_name' => $sensor['dev_name'] . ':' . $sensor['name'],
			'unit' => $sensor['unit'],
			'source_desc' => $viewSource['short_desc'],
			'color' => (new Model\Color(255, 0, 0))->getHtmlColor(),
			'date' => $params->dateTimeFrom->format('d.m.Y'),
			'nr' => 1
		];
		$outView[] = $vi;
		$out['items'] = $outView;
		$out['name'] = $vi['sensor_name'];
		$out['desc'] = $vi['name'];

		//$this->populateChartMenu($id, $sensor->name, 100, $sensor->device_id, $sensor->dev_name);


		if ($sensor['device_class'] == 3) {
			// len pre impulzné senzory - vytiahneme mesačné sumy
			$rs = $this->datasource->getMonthSummaryImp($id);
			$mesicniSumarizace = [];

			foreach ($rs as $row) {
				$rok = substr($row->datum_mesic, 0, 4);
				$mesic = intval(substr($row->datum_mesic, 5, 2));
				$mesicniSumarizace[$rok][$mesic] = $row->suma;
				$prev = isset($mesicniSumarizace[$rok]['celkem']) ? $mesicniSumarizace[$rok]['celkem'] : 0;

				$mesicniSumarizace[$rok]['celkem'] = $prev + $row->suma;
			}
			$out['mesicniSumarizace'] = $mesicniSumarizace;
		} else if ($sensor['device_class'] == 1) {
			// len pre spojité senzory - vytiahneme mesačné min/max/avg
			$rs = $this->datasource->getMonthSummaryCont($id);
			$mesicniSumarizace = [];
			foreach ($rs as $row) {
				$rok = substr($row->datum_mesic, 0, 4);
				$mesic = intval(substr($row->datum_mesic, 5, 2));
				$mesicniSumarizace[$rok][$mesic]['min'] = $row->min_val;
				$mesicniSumarizace[$rok][$mesic]['max'] = $row->max_val;
				$mesicniSumarizace[$rok][$mesic]['avg'] = $row->avg_val;
			}
			$out['mesicniSumarizace'] = $mesicniSumarizace;
		}

		$this->sendJson($out);
	}



	public function actionEdit(int $id) : void {
		
		$_post = json_decode(file_get_contents("php://input"), true);
		
		if ($_post == null) {
			$out = ["status" => 404, "message" => "Nekorektné alebo chýbajúce data z formuláru"];
		} else {
			$values = $_post;
			$values['name'] = $this->user->getIdentity()->prefix.":".$_post['name'];
			$values['user_id'] = $this->user->id;
			$values['passphrase'] = $this->config->encrypt( $_post['passphrase'], $_post['name'] );
			//dumpe($values, $_post, $id);
			if( $id ) {
				// editace
				$device = $this->devices->getDevice( $id );
				//dumpe($device);
				if (!$device) {
					$out = ["status" => 404, "message" => "Zariadenie sa nenašlo"];
				} else if( $this->user->id != $device->attrs->user_id ) {
					Services\Logger::log( 'audit', Services\Logger::ERROR , 
						sprintf("Užívateľ #%s (%s) zkúsil editovať zariadenie patriace užívateľovi #%s", $this->user->id, $this->user->getIdentity()->email, $device->user_id));
					$this->user->logout(true);
					$out = ["status" => 500, "message" => "K tomuto zariadeniu nemáte oprávnený prístup!"];
				} else {
					
					$up = $device->attrs->update( $values );
					//$up = $this->devices->getDeviceSimple($id)->update( $values );
					$out = ["status" => 200, "message" => "Údaje zariadenia aktualizované."];
					//dumpe($out);
				}
			} else { // zalozeni
				try {
					$new_device = $this->devices->createDevice( $values , true );
					$out = ["status" => 200, "message" => "Zariadenie bolo vytvorené.", "device" => $new_device];
				} catch (Database\UniqueConstraintViolationException  $e) {
					$out = ["status" => 500, 
									"message" => "Chyba pri vytváraní zariadenia: 
															 s názvom '".$values["name"]."' už existuje. Prosím, zvoľte iný názov."
								 ];
				} catch (\Exception $e) {
					$out = ["status" => 500, 
									"message" => "Chyba pri vytváraní zariadenia: " . $e->getMessage()
								];
				}
			}
		}
		
		$this->sendJson($out);
	}

	public function actionSensoredit(int $id) : void {
		
		$_post = json_decode(file_get_contents("php://input"), true);
		
		if ($_post == null) {
			$out = ["status" => 404, "message" => "Nekorektné alebo chýbajúce data z formuláru"];
		} else {
			$values = $_post;
			$sensor = $this->sensors->getSensor($id);
			if (!$sensor) {
				$out = ["status" => 404, "message" => "Senzor sa nenašiel"];
			} else if( $this->user->id != $sensor->device->user_id ) {
				Services\Logger::log( 'audit', Services\Logger::ERROR , 
					sprintf("Užívateľ #%s (%s) zkúsil editovať senzor patriaci zariadeniu užívateľa #%s", $this->user->id, $this->user->getIdentity()->email, $sensor->device->user_id));
				$this->user->logout(true);
				$out = ["status" => 500, "message" => "K tomuto senzoru nemáte oprávnený prístup!"];
			} else {
				unset($values['dev_name'], $values['dev_desc'], $values['user_id'], $values['unit']); 
				$out = ($this->sensors->save($id, $values) === null) ? ["status" => 500, "message" => "Chyba pri ukladaní údajov senzora."] : $this->sensors->getAndCheckSensorAccess($id);
			}
		}
		$this->sendJson($out);
	}
}
