<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Table\Selection;

/**
 * Model, ktory sa stara o tabulku updates
 * 
 * Posledna zmena 24.04.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
class PV_Updates extends Table
{

  /** @var string */
  protected $tableName = 'updates';

  public function getOtaUpdates(int $id): Selection|null
  {
    $_t = $this->findBy(["device_id" => $id])->order("id ASC");
    return $_t->count() ? $_t : null;
  }

  public function otaDeleteUpdate(int $deviceId, int $updateId): void
  {
    $this->findOneBy(["id" => $updateId, "device_id" => $deviceId])->delete();
  }

  /**
   * @return int záznamu alebo -1, pokiaľ pre dané zariadenie a verziu už existuje
   */
  public function otaUpdateCreate(int $id, int $fromVersion, string $fileHash): int
  {
    $rs1 = $this->findBy(["device_id" => $id, "fromVersion" => $fromVersion])->count();
    if ($rs1 != 0) {
      return -1;
    } else {
      return $this->pridaj([
        'device_id' => $id,
        'fromVersion' => $fromVersion,
        'fileHash' => $fileHash,
        'inserted' => new \DateTime(),
      ])->id;
    }
    /*
    $this->database->query('INSERT INTO updates ', [
      'device_id' => $id,
      'fromVersion' => $fromVersion,
      'fileHash' => $fileHash,
      'inserted' => new \DateTime(),
    ]);

    return $this->database->getInsertId();*/
  }
}
