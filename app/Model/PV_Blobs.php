<?php

namespace App\Model;

use Nette\Database;

/**
 * Model, ktory sa stara o tabulku blobs
 * 
 * Posledna zmena 14.07.2022
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2022 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class PV_Blobs extends Table
{

  /** @var string */
  protected $tableName = 'blobs';

  public function getBlobCount(int $deviceId): int
  {
    return $this->findBy(['device_id' => $deviceId, 'status > 0'])->count('*');
  }

  public function getBlobs(int $deviceId): Database\Table\Selection
  {
    return $this->findBy(['device_id' => $deviceId, 'status > 0'])->order("id DESC");
  }


  public function getBlob(int $deviceId, int $blobId): ?Database\Table\ActiveRow
  {
    return $this->findOneBy(['id' => $blobId, 'device_id' => $deviceId, 'status > 0']);
  }
}
