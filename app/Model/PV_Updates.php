<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

/**
 * Model, ktory sa stara o tabulku updates
 * 
 * Posledna zmena 09.11.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.4
 */
class PV_Updates extends Table
{
	protected string $tableName = 'updates';

	public function getOtaUpdates(int $id, bool $return_as_array = false): Selection|array|null
	{
		$_t = $this->findBy(["device_id" => $id])->order("id ASC");
		$_t = $_t->count() ? $_t : null;
		if ($return_as_array && $_t) {
			$_o = [];
			foreach ($_t as $row) {
				$_o[] = [
					'id' => $row->id,
					'fromVersion' => $row->fromVersion,
					'fileHash' => $row->fileHash,
					'filename' => $row->filename,
					'inserted' => $row->inserted ? $row->inserted->format('d.m.Y H:i:s') : null,
					'downloaded'=> $row->downloaded ? $row->downloaded->format('d.m.Y H:i:s') : null,
				];
			}
			$_t = $_o;
		}
		return $_t;
	}

	public function otaDeleteUpdate(int $deviceId, int $updateId): void
	{
		$this->findOneBy(["id" => $updateId, "device_id" => $deviceId])->delete();
	}

	/**
	 * @return int záznamu alebo -1, pokiaľ pre dané zariadenie a verziu už existuje
	 */
	public function otaUpdateCreate(int $id, string $fromVersion, string $fileHash, string $filename, bool $return_as_array = false): ActiveRow|array|int|null
	{
		$rs1 = $this->findBy(["device_id" => $id, "fromVersion" => $fromVersion])->count();
		if ($rs1 != 0) {
			return -1;
		} else {
			$_t = $this->add([
				'device_id' => $id,
				'fromVersion' => $fromVersion,
				'fileHash' => $fileHash,
				'filename' => $filename,
				'inserted' => new \DateTime(),
			]);
			if ($return_as_array && $_t) {
				$_t = [
					'id' => $_t->id,
					'fromVersion' => $_t->fromVersion,
					'fileHash' => $_t->fileHash,
					'filename' => $_t->filename,
					'inserted' => $_t->inserted ? $_t->inserted->format('d.m.Y H:i:s') : null,
					'downloaded'=> $_t->downloaded ? $_t->downloaded->format('d.m.Y H:i:s') : null,
				];
			}
			return $_t;
		}
	}
}
