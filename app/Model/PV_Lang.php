<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model starajuci sa o tabulku lang
 * 
 * Posledna zmena 27.06.2022
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2022 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class PV_Lang extends Table {
  /** @var string */
  protected $tableName = 'lang';
  
  public function langsForForm(): array
  {
    return $this->findAll()->fetchPairs('id', 'name');
  }

}