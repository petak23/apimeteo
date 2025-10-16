<?php

namespace App\Presenters;

/**
 * Domáci presenter pre API.
 * Posledna zmena(last change): 14.10.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.2
 */
class HomepagePresenter extends BasePresenter
{

	public function actionMyAppSettings(): void
	{
		$out = $this->config->getConfigs();
		if (!$this->user->isLoggedIn()) unset($out['masterPassword']);
		$out['basePath'] = $this->template->basePath;
		$this->sendJson($out);
	}
}
