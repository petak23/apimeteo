<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model, ktorý sa stará o tabuľku user_state
 * 
 * Posledná zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class User_state extends Table {

  protected string $tableName = 'user_state';

  public function getAllForForm(): array 
  {
    return $this->findAll()->fetchPairs('id', 'desc');
  }
}