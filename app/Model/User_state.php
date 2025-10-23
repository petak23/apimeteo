<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model, ktorý sa stará o tabuľku user_state
 * 
 * Posledná zmena 23.10.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class User_state extends Table {

  /** @var string */
  protected $tableName = 'user_state';


  public function getAllForForm(): array {
    return $this->findAll()->fetchPairs('id', 'desc');
  }
}