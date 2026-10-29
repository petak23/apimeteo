<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use Nette;

/**
 * Presenter pre prácu s monitoringom
 * Posledna zmena 29.10.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2022 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
final class MonitorPresenter extends BasePresenter
{
  use Nette\SmartObject;

  // Database tables
  /** @var Model\PV_Devices @inject */
  public $devices;

  // http://apimeteo.echo-msz.eu/monitor/show/aaabbb/2/
  public function renderShow(string $id, string $token)
  {
    $userData = $this->user_main->getUser((int)$id);
    if ($userData == null) {
      $this->error('Užívateľ s daným id nebol nájdený...');
    }
    if (!$token || ($userData['monitoring_token'] !== $token)) {
      $this->error('Token nesúhlasí.');
    }
    $this->template->devices = $this->devices->getDevicesUser((int)$id);

    $response = $this->getHttpResponse();
    $response->setHeader('Cache-Control', 'no-cache');
    $response->setExpiration('1 sec');
  }
}
