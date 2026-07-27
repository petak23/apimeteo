<?php

declare(strict_types=1);

namespace App\Services;

use App\Model;
use Nette;
use Nette\Utils\DateTime;
use Tracy\Debugger;

use \App\Model\SensorDataSeries;
use \App\Model\ChartPoint;
use \App\Model\View;
use \App\Model\ViewItem;
use \App\Services\Logger;
use DateInterval;

use function intval, strlen ;

class ChartDataSource 
{
	use Nette\SmartObject;

	/** @var Model\PV_Sensors */
	public $pv_sensors;
	
	private Nette\Database\Explorer $database;
	
	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

	private function computeOffset(DateTime $date, DateInterval $time, int $startTs ) : int
	{
		$rc = $date->getTimestamp();
		$rc += $time->h * 3600 + $time->i * 60 + $time->s;
		$rc -= $startTs;
		return $rc;
	}

	/**
	 * Dáta pre graf pokrytia
	 */
	public function getSensorCoverageData( int $sensor_id, int $year ) 
	{
		$startTs = "{$year}-01-01";
		$endTs = ($year+1) . '-01-01';

		$result = $this->database->query('
			select rec_date	, avg_val , ct_val	
			from sumdata
			where
			sensor_id = ?
			and sum_type = 2
			and rec_date >= ?
			and rec_date < ?
			order by rec_date asc
		', $sensor_id, $startTs, $endTs );

		return $result;
	}


	/**
	 * Dáta pre priemer - z dennej sumarizácie
	 */
	public function getAvgData(array $sensors, int $year , int $years ) 
	{
		$startTs = "{$year}-01-01";
		$endTs = ($year+$years) . '-01-01';  // 

		$sensorList = "";
		foreach( $sensors as $sensor ) {
			if( strlen($sensorList)>0 ) {
				$sensorList .= ",";
			}
			$sensorList .= intval($sensor['id']);
		}

		return $this->database->query( "
			SELECT sensor_id, rec_date, rec_hour, min_val, max_val, avg_val, ct_val
			from sumdata
			where rec_date >= ?
			and rec_date < ?
			and sensor_id in ( $sensorList )
			and sum_type = 2
			order by rec_date asc, sensor_id asc
		", $startTs , $endTs );
	}


	//TODO: datasource pre dennú sumarizáciu, ktorú ešte nemáme spočítanú


	/**
	 * Vracia dáta pre graf z detailných dát
	 * Hodí sa pre graf teploty
	 * 
	 * Vracia objekt SensorDataSeries
	 */
	public function getSensorData_temperature_detail(array $sensor, Nette\Utils\DateTime $dateTimeFrom, int $intervalLenDays ) : SensorDataSeries
	{
		$startTs = $dateTimeFrom->getTimestamp();
		$dateTimeTo = $dateTimeFrom->modifyClone('+' . $intervalLenDays . ' day');   

		$rc = new SensorDataSeries( $sensor );

		$result = $this->database->query('
			select data_time, out_value
			from measures
			where 
			sensor_id = ?
			and data_time > ?
			and data_time <= ?
			order by data_time asc
		', $sensor['id'], $dateTimeFrom , $dateTimeTo  );

		foreach ($result as $row) {
			// Debugger::log( $row );
			$relTime = $row->data_time->getTimestamp() - $startTs ;
			$rc->pushPoint( new ChartPoint( $relTime, floatval($row->out_value )) );
		}

		// Debugger::log( $rc->toString( TRUE ) );
		return $rc;
	}

	
	/**
	 * Vracia dáta pre graf z min/max hodnôt hodinových sumarizácií.
	 * Hodí sa teda pre graf teploty, kde na každý deň zostane 2x24 = 48 px (na šírku 1500 bodov = 31 dní)
	 * 
	 * Vracia objekt SensorDataSeries
	 */
	public function getSensorData_temperature_summary( $sensors, DateTime $dateTimeFrom, int $intervalLenDays ) : SensorDataSeries
	{
		$startTs = $dateTimeFrom->getTimestamp();
		$dateTimeTo = $dateTimeFrom->modifyClone('+' . $intervalLenDays . ' day');   

		$rc = new SensorDataSeries( $sensors[0] );

		$sensorList = "";
		foreach( $sensors as $sensor ) {
			if( strlen($sensorList)>0 ) {
				$sensorList .= ",";
			}
			$sensorList .= intval($sensor['id']);
		}

		$result = $this->database->query( "
			SELECT sensor_id, rec_date, rec_hour, min_val, min_time, max_val, max_time, avg_val
			from sumdata
			where rec_date >= ?
			and rec_date < ?
			and sensor_id in ( $sensorList )
			and sum_type = 1
			order by rec_date asc, rec_hour asc, sensor_id asc
		", $dateTimeFrom , $dateTimeTo );

		// poznamka - casy TIME se vraceji jako PHP DateInterval

		$prevDate = NULL;
		$prevHour = NULL;

		foreach ($result as $row) {
			// Debugger::log( $row );

			if( $prevDate === NULL ) {
				$prevDate = $row->rec_date;
				$prevHour = $row->rec_hour;
			} else if( $prevDate==$row->rec_date && $prevHour == $row->rec_hour ) {
				// dáta z ďalšieho senzora pre rovnakú hodinu ignorujeme
				continue;
			}
			$prevDate = $row->rec_date;
			$prevHour = $row->rec_hour;

			$minRelTime = $this->computeOffset( $row->rec_date, $row->min_time, $startTs );
			$maxRelTime = $this->computeOffset( $row->rec_date, $row->max_time, $startTs );

			if( $minRelTime < $maxRelTime ) {
				$rc->pushPoint( new ChartPoint( $minRelTime, floatval($row->min_val) ) );
				$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val) ) );
			} else if( $minRelTime > $maxRelTime ) { 
				$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val) ) );
				$rc->pushPoint( new ChartPoint( $minRelTime, floatval($row->min_val) ) );
			} else {
				// máme len jeden bod
				$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val) ) );
			}
		}

		// Debugger::log( $rc->toString( TRUE ) );

		return $rc;
	}


	private function computeOffset1200(DateTime $date, int $startTs ) : int
	{
		$rc = $date->getTimestamp();
		$rc += 12 * 3600;
		$rc -= $startTs;
		return $rc;
	}

	private function computeOffsetWeeksum(DateTime $date, int $startTs ) : int
	{
		$rc = $date->getTimestamp();
		$rc += 1*86400 + 12*3600;
		$rc -= $startTs;
		return $rc;
	}

	/**
	 * Vracia dáta pre graf z min/max/avg hodnôt denných/hodinových sumarizácií.
	 * 
	 * mode: 
	 * - 1=denni min
	 * - 2=denni max
	 * - 3=denni avg
	 * - 4=denni sum
	 * - 5 = hodinovy sum
	 * - 6 = vrati denni minimum A maximum
	 * - 7 = hodinove maximum
	 * 
	 * 
	 * Vraci objekt SensorDataSeries
	 */
	public function getSensorData_minmaxavg_daysummary( array $sensors, DateTime $dateTimeFrom, int $intervalLenDays, int $mode ) : SensorDataSeries
	{
		$startTs = $dateTimeFrom->getTimestamp();
		$dateTimeTo = $dateTimeFrom->modifyClone('+' . $intervalLenDays . ' day');   

		$rc = new SensorDataSeries( $sensors[0] );

		$sensorList = "";
		foreach( $sensors as $sensor ) {
			if( strlen($sensorList)>0 ) {
				$sensorList .= ",";
			}
			$sensorList .= intval($sensor['id']);
		}

		$sum_type = 2;
		if( $mode==5 || $mode==7 ) {
			// len pre 5 a 7 sú hodinové sumarizácie
			$sum_type = 1;
		}

		$result = $this->database->query("
			SELECT sensor_id, rec_date, rec_hour, min_val, min_time, max_val, max_time, avg_val, sum_val
			from sumdata
			where rec_date >= ?
			and rec_date < ?
			and sensor_id in ( $sensorList )
			and sum_type = ?
			order by rec_date asc, rec_hour asc
		", $dateTimeFrom , $dateTimeTo , $sum_type );

		// Debugger::log( "loading  $sensorId, $dateTimeFrom, $intervalLenDays, $mode " );

		$prevDate = NULL;
		$prevHour = NULL;

		// poznamka - casy TIME se vraceji jako PHP DateInterval
		foreach ($result as $row) {
			// Debugger::log( $row );

			if( $prevDate === NULL ) {
				$prevDate = $row->rec_date;
				$prevHour = $row->rec_hour;
			} else if( $prevDate==$row->rec_date && $prevHour == $row->rec_hour ) {
				// dáta z ďalšieho senzora pre rovnakú hodinu ignorujeme
				continue;
			}
			$prevDate = $row->rec_date;
			$prevHour = $row->rec_hour;

			$relTime = $this->computeOffset1200( $row->rec_date, $startTs );

			if( $mode == 1 )
			{
				// denni min

				/* Nyni se nastavuje relativni cas 12:00.
				 * Kdyby se povolila nasledujici radka, vykreslovalo by se to ve skutecnem case minima:
				 *  $relTime = $this->computeOffset( $row->rec_date, $row->min_time, $startTs );
				 * ale ukazuje sa, že v tom prípade sa napríklad čiary maxima a minima prekrývajú.
				 * ale ukazuje sa, že v tom prípade sa napríklad čiary maxima a minima prekrývajú.
				 * Aby to fungovalo, je treba v SensorDataSeries->pushPoint nastavit misto 90000 hodnotu 2*86400
				*/ 
				$relTime = $this->computeOffset( $row->rec_date, $row->min_time, $startTs );
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->min_val) ), TRUE );
			} else if( $mode == 2 ) {
				// denni max
				$relTime = $this->computeOffset( $row->rec_date, $row->max_time, $startTs );
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->max_val) ), TRUE );
			} else if( $mode == 3 && ($row->avg_val!=NULL) ) {
				// denni avg
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->avg_val) ), TRUE );
			} else if( $mode == 4 ) {
				// denni suma
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->sum_val) ), TRUE );
			} else if( $mode == 5 ) {
				// hodinova suma
				// odčítame 12, pretože vyššie sa počíta offset pre 12:00
				$relTime += ($row->rec_hour-12)*3600 + 1800;
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->sum_val) ) );

			} else if( $mode == 7 ) {

				// hodinove maximum
				$relTime = $this->computeOffset( $row->rec_date, $row->max_time, $startTs );
				$rc->pushPoint( new ChartPoint( $relTime, floatval($row->max_val) ) );

			} else if( $mode == 6 ) {
				// denni minimum A maximum

				$minRelTime = $this->computeOffset( $row->rec_date, $row->min_time, $startTs );
				$maxRelTime = $this->computeOffset( $row->rec_date, $row->max_time, $startTs );
	
				if( $minRelTime < $maxRelTime ) {
					$rc->pushPoint( new ChartPoint( $minRelTime, floatval($row->min_val)  ), TRUE );
					$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val)  ), TRUE );
				} else if( $minRelTime > $maxRelTime ) { 
					$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val)  ) , TRUE);
					$rc->pushPoint( new ChartPoint( $minRelTime, floatval($row->min_val)  ) , TRUE);
				} else {
					// mame jen jeden bod
					$rc->pushPoint( new ChartPoint( $maxRelTime, floatval($row->max_val)  ) , TRUE);
				} 
			}
		}

		// Debugger::log( $rc->toString( TRUE ) );

		return $rc;
	}



	public function getSensorData_weeksummary(array $sensors, DateTime $dateTimeFrom, int $intervalLenDays ) : SensorDataSeries
	{
		Logger::log( 'webapp', Logger::DEBUG ,  "weeksumary < $dateTimeFrom, $intervalLenDays" ); 

		// pokud startTs neni pondeli, vzit nejblizsi predesle pondeli
		$denVTydnu = intval($dateTimeFrom->format('N'));
		if( $denVTydnu!=1 ) {
			$offset = $denVTydnu-1;
			$dateTimeFrom->modify( "-$offset day");
		}
		$zbytek = $intervalLenDays % 7;
		if( $zbytek!=0 ) {
			$intervalLenDays = 7 * (intval($intervalLenDays / 7)+1);
		}
		Logger::log( 'webapp', Logger::DEBUG ,  "weeksumary > $dateTimeFrom, $intervalLenDays" ); 

		$startTs = $dateTimeFrom->getTimestamp();
		$dateTimeTo = $dateTimeFrom->modifyClone('+' . $intervalLenDays . ' day');   

		$rc = new SensorDataSeries( $sensors[0] );

		$sensorList = "";
		foreach( $sensors as $sensor ) {
			if( strlen($sensorList)>0 ) {
				$sensorList .= ",";
			}
			$sensorList .= intval($sensor['id']);
		}

		$result = $this->database->query("
			SELECT sensor_id, rec_date, WEEK(rec_date,3) as week, rec_hour, min_val, min_time, max_val, max_time, avg_val, sum_val
			from sumdata
			where rec_date >= ?
			and rec_date < ?
			and sensor_id in ( $sensorList )
			and sum_type = 2
			order by rec_date asc, rec_hour asc
		", $dateTimeFrom , $dateTimeTo  );

		// Debugger::log( "loading  $sensorId, $dateTimeFrom, $intervalLenDays, $mode " );

		$prevWeek = NULL;
		$curRelTime = NULL;
		$curSum = 0;

		// poznamka - casy TIME se vraceji jako PHP DateInterval
		foreach ($result as $row) {
			// Debugger::log( $row );

			$relTime = $this->computeOffsetWeeksum( $row->rec_date, $startTs );

			if( $prevWeek===NULL ) {
				$prevWeek = $row->week;
				$curRelTime = $relTime;
				$curSum = floatval($row->sum_val);
			}

			if( $prevWeek != $row->week ) {
				$rc->pushPoint( new ChartPoint( $curRelTime, $curSum ) );

				$prevWeek = $row->week;
				$curRelTime = $relTime;
				$curSum = floatval($row->sum_val);
			} else {
				$curSum += floatval($row->sum_val);
			}
		}

		if( $prevWeek!==NULL ) {
			$rc->pushPoint( new ChartPoint( $curRelTime, $curSum ) );
		}

		// Debugger::log( $rc->toString( TRUE ) );

		return $rc;
	}


	/**
	 * id	desc	short_desc
	 */
	public function getViewSource(int $id )
	{
		return $this->database->fetch('

			SELECT * 
			from view_source
			WHERE id = ?

		', $id );
	}

	public function getView(int $id, string $token): View
	{
		$viewMeta = $this->database->fetch('
			select vdesc, name, render, allow_compare, app_name from views
			where id = ?
			and token = ?
		', $id, $token );

		if( $viewMeta == NULL ) {
			throw new \Exception( "View {$id} not found or invalid token {$token}.");
		}

		$view = new View( $viewMeta->name, $viewMeta->vdesc, $viewMeta->allow_compare, $viewMeta->app_name, $viewMeta->render );

		/* id	view_id	vorder	sensor_ids	y_axis	view_source_id	color_1	color_2	view_source_desc */
		$result = $this->database->query('
			select vd.*, 
			vs.short_desc as view_source_desc
			
			from view_detail vd
			
			left outer join view_source vs
			on vd.view_source_id = vs.id
			
			where vd.view_id = ?
			order by vorder asc   
		', $id );

		// poznamka - casy TIME se vraceji jako PHP DateInterval

		foreach ($result as $row) {
			$vi = new ViewItem();

			$sids = explode( ',' , $row->sensor_ids );
			foreach( $sids as $sid ) {
				$vi->pushSensor( $this->pv_sensors->getSensor((int)$sid, true));
			}

			$vi->axisY = $row->y_axis;
			$vi->source = $row->view_source_id;
			$vi->sourceDesc = $row->view_source_desc;
			$vi->setColor( 1, $row->color_1 );
			$vi->setColor( 2, $row->color_2 );
			// Debugger::log( $vi->toString() );
			
			$view->items[] = $vi;
		}

		return $view;
	}

	public function readViews( $token )
	{
		return $this->database->fetchAll(  '
			select id, name from views
			where token=?
			order by vorder desc
			', $token  );
	}

	public function getMeasuresStats(int $sensorId): array
	{
		$out = $this->database->table('measures')
				->select('MIN(data_time) AS min_time, MAX(data_time) AS max_time, COUNT(*) AS count')
				->where('sensor_id', $sensorId)
				->fetch()->toArray();
		$out['min_time'] = $out['min_time'] ? $out['min_time']->format('Y-m-d') : null;
		$out['max_time'] = $out['max_time'] ? $out['max_time']->format('Y-m-d') : null;
		return $out;
	}

	public function getSumdataStats(int $sensorId ): array
	{
		$out = $this->database->table('sumdata')
				->select('MIN(rec_date) AS min_date, MAX(rec_date) AS max_date, COUNT(*) AS count')
				->where('sensor_id', $sensorId)
				->fetch()->toArray();
		$out['min_date'] = $out['min_date'] ? $out['min_date']->format('Y-m-d') : null;
		$out['max_date'] = $out['max_date'] ? $out['max_date']->format('Y-m-d') : null;
		return $out;
	}

	public function getSumdataCount(int $sensorId ): array
	{
		$out = [];

		$rs = $this->database->table('sumdata')
		    ->select('sum_type, COUNT(*) AS count')
		    ->where('sensor_id', $sensorId)
		    ->group('sum_type')
		    ->order('sum_type')
		    ->fetchAll();

		foreach( $rs as $row ) {
			if( $row->sum_type == 1 ) {
				$out['hour'] = $row->count;
			} else if( $row->sum_type == 2 ) {
				$out['day'] = $row->count;
			}
		}

		return $out;
	}

	public function getMonthSummaryCont($sensorId)
	{
		// prepnuté na Explorer fluent API namiesto raw SQL
		return $this->database->table('sumdata')
			->select("DATE_FORMAT(rec_date, ?) AS datum_mesic", '%Y-%m') 
			->select('MIN(min_val) AS min_val')
			->select('MAX(max_val) AS max_val')
			->select('AVG(avg_val) AS avg_val')
			->where('sensor_id', $sensorId)
			->where('sum_type', 2)
			->group('datum_mesic')
			->order('datum_mesic ASC')
			->fetchAll();
	}
}



