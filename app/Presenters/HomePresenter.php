<?php

namespace App\Presenters;

/**
 * Domáci presenter pre API.
 * Posledna zmena(last change): 20.11.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.0
 */
class HomePresenter extends BasePresenter
{

	public function actionMyAppSettings(): void
	{
		$out = $this->config->getConfigs();
		$out['basePath'] = $this->template->basePath;
		$this->sendJson($out);
	}
}
