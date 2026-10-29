<?php

namespace App\Presenters;

/**
 * Domáci presenter pre API.
 * Posledna zmena(last change): 29.10.2026
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.3
 */
class HomepagePresenter extends BasePresenter
{
	public function renderDefault(): void
	{
		$_u = $this->user_main->getUser($this->user->getId());

		$this->template->monitoring_token = $_u !== null ? $_u->monitoring_token : null;
		$this->template->user_id = $_u !== null ? (int)$_u->id : null;
	}

	public function actionMyAppSettings(): void
	{
		$out = $this->config->getConfigs();
		if (!$this->user->isLoggedIn()) unset($out['masterPassword']);
		$out['basePath'] = $this->template->basePath;
		$this->sendJson($out);
	}
}
