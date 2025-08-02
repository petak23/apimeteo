<?php

namespace App\Presenters;

/**
 * Domáci presenter pre API.
 * Posledna zmena(last change): 02.08.2025
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 */
class HomepagePresenter extends BasePresenter
{

	public function actionMyAppSettings(): void
	{
		$out = $this->config->getConfigs();
		$out['basePath'] = $this->template->basePath;
		$this->sendJson($out);
	}
}
