<?php

namespace App\Presenters;

use App\Model;
use App\Services;
use Nette\Database;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

/**
 * Prezenter pre pristup k api senzorov.
 * Posledna zmena(last change): 24.02.2026
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.6
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

	public function __construct(Services\Config $config) {
		$this->config = $config;
	}

	/** Vráti zoznam senzorov pre dané zariadenie */
	public function actionSensors(int $id): void
	{
		$d = $this->devices->getDevice($id, true, true);
		$this->sendJson($d["sensors"]);
	}

	public function actionSensor(int $id, int $detail = 0): void
	{
		$sensor = $this->sensors->getSensor($id, true);
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
	 * Rendering statistiky pro senzor - volano z administrace, autentizovany uzivatel.
	 */
	public function actionSensorstat(
		$id,
		$dateFrom = "",
		$lenDays = 7,
		$altYear = "",
		$plus = "",
		$minus = "",
		$altplus = "",
		$altminus = "",
		$current = "",
		$currentweek = "",
		$currentmonth = "",
		$currentyear = "",
		$plusMon = "",
		$minusMon = "",
		$plusYear = "",
		$minusYear = "",
		$currentday = ""
	) {

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

		$params->allowCompare(true);
		$chart = new Model\Chart(null);
		$sensor = $this->datasource->getSensor($id);
		//$this->checkSensorAccess($sensor != NULL ? $sensor->user_id : NULL, $id);
		$device = [
			'name' => $sensor->dev_name,
			'desc' => $sensor->dev_desc
		];
		
		$this->devices[$sensor->device_id] = $device;

		$out = [
			'allowCompare' => TRUE,
			'id' => $id,
			'dateFrom' => $params->dateTimeFrom->format('Y-m-d'),
			'lenDays' => $params->lenDays,
			'altYear' => $params->altYear,
			'appName' => $this->config->appName,
			'chW' => $chart->width(),
			'chH' => $chart->height(),
			// sirka sloupce pro vykresleni - obrazek + mala rezerva
			'maxW' => $chart->width() + 85,

			'dataRetentionDays' => $this->config->dataRetentionDays,
			'links' => $this->config->links,

			'sensor' => $sensor,

			'source1' => TRUE,
			'isKompozit' => FALSE,

			'isChart' => TRUE,
			'path' => "../../../",

			'measureStats' => $this->datasource->getMeasuresStats($id),
			'sumdataStats' => $this->datasource->getSumdataStats($id),
			'sumdataCount' => $this->datasource->getSumdataCount($id),

			'devices' => $this->devices,
			'years' => $params->getAltYearsList(),
			'minYear' => $this->config->minYear

		];

		$viewSource = $this->datasource->getViewSource($this->getViewSourceId($sensor->device_class, $params->lenDays));
		
		$outView = [];
		$vi = [
			'sensor_ids' => $id,
			'axis' => 1,
			'name' => $sensor->desc,
			'sensor_name' => $sensor->dev_name . ':' . $sensor->name,
			'unit' => $sensor->unit,
			'source_desc' => $viewSource->short_desc,
			'color' => (new Model\Color(255, 0, 0))->getHtmlColor(),
			'date' => $params->dateTimeFrom->format('d.m.Y'),
			'nr' => 1
		];
		$outView[] = $vi;
		$out['items'] = $outView;
		$out['name'] = $vi['sensor_name'];
		$out['desc'] = $vi['name'];

		//$this->populateChartMenu($id, $sensor->name, 100, $sensor->device_id, $sensor->dev_name);


		if ($sensor->device_class == 3) {
			// jen pro impulzni senzory - vytahneme mesicni sumy
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
		} else if ($sensor->device_class == 1) {
			// jen pro spojite senzory - vytahneme mesicni min/max/avg
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
		
		/*if ($_post == null) {
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
		
		$this->sendJson($out);*/
	}
}
