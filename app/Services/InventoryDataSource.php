<?php

declare(strict_types=1);

namespace App\Services;

use Nette;

use \App\Model\View;
use \App\Model\ViewItem;


/**
 * @last_edited petak23<petak23@gmail.com> 09.06.2023
 */
class InventoryDataSource
{
  use Nette\SmartObject;

  private $database;

  public function __construct(Nette\Database\Explorer $database)
  {
    $this->database = $database;
  }

  /* *
     * @deprecated use PV_Devices\getDevice
     */
  //------

  /**
   * @deprecated not used
   */
  public function getSensor($sensorId)
  {
    return $this->database->fetch('
            select 
                s.*, 
                d.name as dev_name, d.desc as dev_desc, d.user_id,  
                vt.unit
            from sensors s

            left outer join devices d
            on s.device_id = d.id

            left outer join value_types vt
            on s.id_value_types = vt.id
            
            where s.id = ?
        ', $sensorId);
  }

  public function updateSensor($id, $values)
  {
    $outvalues = [];
    $outvalues['desc'] = $values['desc'];
    $outvalues['display_nodata_interval'] = $values['display_nodata_interval'];
    $outvalues['preprocess_data'] = $values['preprocess_data'];
    $outvalues['preprocess_factor'] =  ($values['preprocess_data'] == '1' ? $values['preprocess_factor'] : "1");

    if (isset($values['warn_max'])) {
      $outvalues['warn_max'] = $values['warn_max'];
      $outvalues['warn_max_val'] = ($values['warn_max'] == '1' ? $values['warn_max_val'] : 0);
      $outvalues['warn_max_val_off'] = ($values['warn_max'] == '1' ? $values['warn_max_val_off'] : 0);
      $outvalues['warn_max_after'] = ($values['warn_max'] == '1' ? $values['warn_max_after'] : 0);
      $outvalues['warn_max_text'] = $values['warn_max_text'];
      $outvalues['warn_min'] = $values['warn_min'];
      $outvalues['warn_min_val'] = ($values['warn_min'] == '1' ? $values['warn_min_val'] : 0);
      $outvalues['warn_min_val_off'] = ($values['warn_min'] == '1' ? $values['warn_min_val_off'] : 0);
      $outvalues['warn_min_after'] = ($values['warn_min'] == '1' ? $values['warn_min_after'] : 0);
      $outvalues['warn_min_text'] = $values['warn_min_text'];
    }

    $this->database->query('UPDATE sensors SET ', $outvalues, ' WHERE id = ?', $id);
  }



  public $views;
  public $tokens;
  public $tokenView;

  public function getViews($userId)
  {
    $this->views = array();
    $this->tokens = array();
    $this->tokenView = array();

    // nacteme pohledy

    $result = $this->database->query('
            select * from views
            where user_id = ?
            order by token asc, vorder desc
        ', $userId);

    foreach ($result as $viewMeta) {
      $view = new View($viewMeta->name, $viewMeta->vdesc, $viewMeta->allow_compare, $viewMeta->app_name, $viewMeta->render);
      $view->token = $viewMeta->token;
      $view->vorder = $viewMeta->vorder;
      $view->render = $viewMeta->render;
      $view->id = $viewMeta->id;
      $this->views[$view->id] = $view;

      if (!isset($this->tokens[$view->token])) {
        $this->tokens[$view->token] = $view->token;
      }

      if (isset($this->tokenView[$view->token])) {
        $token = $this->tokenView[$view->token];
        $token[] = $view;
        $this->tokenView[$view->token] = $token;
      } else {
        $token = array();
        $token[] = $view;
        $this->tokenView[$view->token] = $token;
      }
    }

    // a k nim nacteme polozky

    $result = $this->database->query('
            select vd.* , v.user_id, vs.short_desc as view_source_desc
            from view_detail vd
            left outer join views v
            on vd.view_id = v.id
            left outer join view_source vs
            on vd.view_source_id = vs.id
            where v.user_id = ?
            order by view_id asc, vorder asc
        ', $userId);

    foreach ($result as $row) {
      $vi = new ViewItem();
      $vi->vorder = $row->vorder;
      $vi->axisY = $row->y_axis;
      $vi->source = $row->view_source_id;
      $vi->sourceDesc = $row->view_source_desc;
      $vi->setColor(1, $row->color_1);
      $vi->setColor(2, $row->color_2);
      $vi->id = $row->id;

      $sids = explode(',', $row->sensor_ids);
      $vi->sensorIds = $sids;

      $this->views[$row->view_id]->items[] = $vi;
    }
  }


  public function getSensors($userId)
  {
    $sensors = array();

    $result = $this->database->query('
            select 
                s.*, 
                d.name as dev_name, d.desc as dev_desc, d.user_id,
                vt.unit
            from sensors s
            left outer join devices d
            on s.device_id = d.id
            left outer join value_types vt
            on s.id_value_types = vt.id
            where d.user_id = ?
            order by vt.unit asc, d.name asc, s.name asc
        ', $userId);

    foreach ($result as $row) {
      $sensors[$row->id] = $row;
    }

    return $sensors;
  }


  public function updateView($id, $values)
  {
    $this->database->query('UPDATE views SET ', [
      'vdesc' => $values['vdesc'],
      'name' => $values['name'],
      'app_name' => $values['app_name'],
      'token' => $values['token'],
      'render' => $values['render'],
      'vorder' => $values['vorder'],
      'allow_compare' => $values['allow_compare']
    ], 'WHERE id = ?', $id);
  }


  public function createView($values)
  {
    return $this->database->table('views')->insert($values);
  }

  public function getViewSources()
  {
    return $this->database->table('view_source')->fetchAll();
  }


  public function createViewitem($values)
  {
    return $this->database->table('view_detail')->insert($values);
  }


  public function getViewItem($viId)
  {
    return $this->database->fetch('
            select 
            vi.*,
            v.user_id
            from view_detail vi
            left outer join views v
            on vi.view_id = v.id
            where vi.id=?
        ', $viId);
  }

  public function updateViewItem($id, $values)
  {
    $this->database->query('UPDATE view_detail SET ', [
      'vorder' => $values['vorder'],
      'sensor_ids' => $values['sensor_ids'],
      'y_axis' => $values['y_axis'],
      'view_source_id' => $values['view_source_id'],
      'color_1' => $values['color_1'],
      'color_2' => $values['color_2']
    ], 'WHERE id = ?', $id);
  }

  public function deleteViewItem($id)
  {
    $this->database->query('
            DELETE from view_detail  
            WHERE id = ?
        ', $id);
  }

  public function deleteView($id)
  {
    $this->database->query('
            DELETE from view_detail  
            WHERE view_id = ?
        ', $id);

    $this->database->query('
            DELETE from views  
            WHERE id = ?
        ', $id);
  }

  public function deleteViewsForUser($id)
  {
    $this->database->query('
            delete from view_detail 
            where view_id in ( select id from views where user_id = ? )
        ', $id);

    $this->database->query('
            delete from views where user_id = ? 
        ', $id);
  }

  public function getDeviceSensors($deviceId)
  {
    return $this->database->fetchAll('
            select s.*,
            vt.unit
            from  sensors s
            left outer join value_types vt
            on s.id_value_types = vt.id
            where device_id = ?
            order by id asc
        ', $deviceId);
  }

  public function meteoGetWeekData($sensorId, $dateFrom)
  {
    return $this->database->fetch('
            select sum(sum_val) as celkem, max(max_val) as maximum, min(min_val) as minimum 
            from sumdata
            where sensor_id = ?
            and rec_date > ?
            and sum_type = 2
        ', $sensorId, $dateFrom);
  }

  public function meteoGetDayData($sensorId, $date)
  {
    return $this->database->fetch('
            select sum(sum_val) as celkem, max(max_val) as maximum, min(min_val) as minimum 
            from sumdata
            where sensor_id = ?
            and rec_date = ?
            and sum_type = 2
        ', $sensorId, $date);
  }

  public function meteoGetNightData($sensorId, $dateYesterday, $dateToday)
  {
    return $this->database->fetch('
            select sum(sum_val) as celkem, max(max_val) as maximum, min(min_val) as minimum from sumdata
            where sensor_id = ?
            and 
            ( ( rec_date = ? and rec_hour >=20 ) 
            or 
            (  rec_date = ? and rec_hour <=6  ) 
            )
            and sum_type = 1
        ', $sensorId, $dateYesterday, $dateToday);
  }
}
