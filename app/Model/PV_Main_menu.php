<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Model starajuci sa o tabulku main_menu
 * 
 * Posledna zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class PV_Main_menu extends Table
{
  protected string $tableName = 'main_menu';

  /**
   * @param Nette\Database\Explorer $db
   * @param Nette\Security\User $user */
  public function __construct(Nette\Database\Explorer $db, Nette\Security\User $user)
  {
    parent::__construct($db);
    $this->user = $user;
  }
}
