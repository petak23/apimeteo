<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use App\Services;
//use App\Services\Logger;
//use Nette\Application\UI\Form;
//use Nette\Http\Url;
//use Nette\Utils\Random;

/**
 * Prezenter pre pristup k api grafou.
 * Posledna zmena(last change): 02.02.2026
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.1
 */

final class ViewPresenter extends BasePresenter
{
	//use Nette\SmartObject;

	/** @persistent */
	public $viewid = "";
	
	/** @var Services\InventoryDataSource */
	//private $datasource;

	// -- DB
	/** @var Model\PV_Sensors @inject */
	public $sensors;
	/** @var Model\Views @inject */
	public $views;

	/*public function __construct(Services\InventoryDataSource $datasource )
	{
		$this->datasource = $datasource;
	}*/

	private function doViewsHome( $detailed ): array
	{
		$out = $this->views->readViews( $this->getUser()->id );
		
		$out["sensors"] = $this->sensors->getSensors( $this->getUser()->id );
		//dumpe($out);
		return $out;
	}

	// TODO: je to potrebné?
	public function renderViewsdetail(): void
	{
		$out = $this->doViewsHome( true );
		$this->sendJson( [ 'status' => 200, 'data' =>	$out ] );
	}

	public function renderViews(): void
	{
		$out = $this->doViewsHome( false );
		$this->sendJson( [ 'status' => 200, 'data' =>	$out ] );
	}

	/*
	protected function createComponentViewForm(): Form
	{
		$form = new Form;
		$form->addProtection();

		$form->addText('name', 'Jméno grafu:')
		->setOption('description', 'Bude zobrazeno jako nadpis nad grafem a jako jméno volby v levém menu.'  )
			->setHtmlAttribute('size', 50)
			->setRequired();

		$form->addText('app_name', 'Jméno aplikace:')
			->setOption('description', 'Bude zobrazeno v šedém pruhu nahoře.'  )
			->setHtmlAttribute('size', 50)
			->setRequired();
	
		$form->addTextArea('vdesc', 'Popis:')
			->setHtmlAttribute('rows', 8)
			->setHtmlAttribute('cols', 80)
			->setRequired();

		$form->addText('token', 'Zabezpečovací token:')
			->setOption('description', 'Stane se součástí URL. Zadejte dlouhý náhodný text. Všechny grafy se stejným tokenem budou vidět v jednom bloku a budou mít společné levé menu.'  )
			->addRule(Form::PATTERN, 'Jen písmena, čísla, pomlčka', '([0-9A-Za-z\-]+)')
			->setHtmlAttribute('size', 50)
			->setDefaultValue( Random::generate(40) )
			->setRequired();

		$form->addCheckbox('allow_compare', 'Povolit srovnávání')
			->setOption('description', 'Pokud je zaškrtnuto, bude nabídnuta možnost srovnávání s jiným rokem.'  );

		$renders = [
			'chart' => 'Základní graf',
			'coverage' => 'Zobrazení pokrytí dat',
			'avgtemp' => 'Průměrná teplota',
			'avgyears0' => 'Porovnání průměrné teploty',
			'avgyears1' => 'Porovnání minimální teploty',
			'line' => 'Vodorovné čáry - vhodné pro směr větru',
			'bar' => 'Sloupcový graf - vhodné pro srážky',
		];

		$form->addSelect('render', 'Vykreslovací stroj:', $renders)
			->setDefaultValue('chart')
			->setPrompt('- Zvolte způsob vykreslení -')
			->setRequired();

		$form->addInteger('vorder', 'Pořadí:')
			->setOption('description', 'Pořadí v menu - pokud je více grafů se stejným tokenem, řadí se podle této hodnoty. Vyšší číslo = více nahoře.'  )
			->setDefaultValue(10)
			->setRequired();

		$form->addSubmit('send', 'Uložit')
			->setHtmlAttribute('onclick', 'if( Nette.validateForm(this.form) ) { this.form.submit(); this.disabled=true; } return false;');
			
		$form->onSuccess[] = [$this, 'viewFormSucceeded'];

		$this->makeBootstrap4( $form );
		return $form;
	}

	public function renderCreate(): void {}

	public function actionEdit(int $id): void
	{
		$this->datasource->getViews( $this->getUser()->id );
		$this->template->view = $this->datasource->views[$id];

		$this->template->noneLeft = TRUE;
		foreach( $this->template->view->items as $item ) {
			if( $item->axisY == 1 ) {
				$this->template->noneLeft = FALSE;
			}
		}

		$this->template->sensors = $this->datasource->getSensors( $this->getUser()->id );

		$post = array();
		$post['name'] = $this->template->view->name;
		$post['app_name'] = $this->template->view->appName;
		$post['vdesc'] = $this->template->view->desc;
		$post['token'] = $this->template->view->token;
		$post['render'] = $this->template->view->render;
		$post['vorder'] = $this->template->view->vorder;
		$post['allow_compare'] = $this->template->view->allowCompare;
		$this['viewForm']->setDefaults($post);

		$url = new Url( $this->getHttpRequest()->getUrl()->getBaseUrl() );
		$this->template->url = $url->getAbsoluteUrl() . "chart/view/{$post['token']}/{$id}/?currentweek=1";
	}

	public function viewFormSucceeded(Form $form, array $values): void
	{
		$id = $this->getParameter('id');

		if( $id ) {
			// editace
			$this->datasource->getViews(  $this->getUser()->id );
			if( ! isset($this->datasource->views[$id]) ) {
				Logger::log( 'audit', Logger::ERROR ,
					"Uzivatel #{$this->getUser()->id} {$this->getUser()->getIdentity()->username} zkusil pristoupit k cizimu view {$id}" );
				$this->error('View nebylo nalezeno');
			}
			$this->datasource->updateView( $id, $values );
		} else {
			// zalozeni
			$values['user_id'] =  $this->getUser()->id ;
			$row = $this->datasource->createView( $values );
			$id = $row->id;
		}

		$this->flashMessage("Změny provedeny.", 'success');
		$this->redirect('View:edit', $id );
	}


	public function actionDelete( int $id ): void
	{
		$this->datasource->getViews( $this->getUser()->id );
		$this->template->view = $this->datasource->views[$id];
	}

	protected function createComponentViewdeleteForm(): Form
	{
		$form = new Form;
		$form->addProtection();

		$form->addSubmit('delete', 'Smazat')
			->setHtmlAttribute('onclick', 'if( Nette.validateForm(this.form) ) { this.form.submit(); this.disabled=true; } return false;');
			
		$form->onSuccess[] = [$this, 'viewdeleteFormSucceeded'];

		$this->makeBootstrap4( $form );
		return $form;
	}

	public function viewdeleteFormSucceeded(Form $form, array $values): void
	{
		$id = $this->getParameter('id');

		if( $id ) {
			// tohle je overeni prav, proto to tu musi byt
			$this->datasource->getViews( $this->getUser()->id );
			$view = $this->datasource->views[$id];

			$this->datasource->deleteView( $id );
		} 

		$this->flashMessage("Graf smazán.", 'success');
		$this->redirect('View:views' );
	}*/

}
