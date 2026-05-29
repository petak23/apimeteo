<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database;
use Nette\Utils\DateTime;

/**
 * Model starajuci sa o tabulku notifications
 * 
 * Posledna zmena 29.05.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class PV_Notifications extends Table {
  /** @var string */
  protected $tableName = 'notifications';
  
  public function deleteNotifications(): void
	{
		$dt = new DateTime();
		$dt->modify('-14 day');

    $this->findBy(['status <>' => 0, 'event_ts <' => $dt])->delete();

		/*$this->database->query('
			select n.* 
			from notifications n
			where status<>0
			and event_ts < ?     
		', $dt);*/
	}

	public function getNotifications(): Database\Table\Selection
	{
		return $this->findBy(["status" => 0])->order("id asc");
	}

	/**
	 * Ukonceni notifikace
	 */
	public function close(int $id): void
	{
		$this->findBy(['id' => $id])->update(['status' => 1]);
	}

	/**
	 * Zalozeni notifikace
	 */
	public function insert(
		int $id_devices,
		int $id_sensor, 
		int $notificationType, 
		string|null $customText, 
		float $value, 
		DateTime $eventTime): void
	{
		$this->add([
			'id_devices' =>  $id_devices,
			'id_sensor' => $id_sensor,
			'event_type' => $notificationType,
			'event_ts' => $eventTime,
			'custom_text' => $customText,
			'out_value' => $value,
		]);
	}

}