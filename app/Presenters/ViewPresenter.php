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

		$form->addText('name', 'Názov grafu:')
		->setOption('description', 'Bude zobrazené ako nadpis nad grafom a ako názov voľby v ľavom menu.'  )
			->setHtmlAttribute('size', 50)
			->setRequired();

		$form->addText('app_name', 'Názov aplikácie:')
			->setOption('description', 'Bude zobrazené v sivom pruhu hore.'  )
			->setHtmlAttribute('size', 50)
			->setRequired();
	
		$form->addTextArea('vdesc', 'Popis:')
			->setHtmlAttribute('rows', 8)
			->setHtmlAttribute('cols', 80)
			->setRequired();

		$form->addText('token', 'Zabezpečovací token:')
			->setOption('description', 'Stane sa súčasťou URL. Zadajte dlhý náhodný text. Všetky grafy s rovnakým tokenom budú viditeľné v jednom bloku a budú mať spoločné ľavé menu.'  )
			->addRule(Form::PATTERN, 'Len písmená, čísla, pomlčka', '([0-9A-Za-z\-]+)')
			->setHtmlAttribute('size', 50)
			->setDefaultValue( Random::generate(40) )
			->setRequired();

		$form->addCheckbox('allow_compare', 'Povoliť porovnávanie')
			->setOption('description', 'Ak je zaškrtnuté, bude ponúknutá možnosť porovnávania s iným rokom.'  );

		$renders = [
			'chart' => 'Základný graf',
			'coverage' => 'Zobrazenie pokrytia dát',
			'avgtemp' => 'Priemerná teplota',
			'avgyears0' => 'Porovnanie priemernej teploty',
			'avgyears1' => 'Porovnanie minimálnej teploty',
			'line' => 'Vodorovné čiary - vhodné pre smer vetra',
			'bar' => 'Stĺpcový graf - vhodné pre zrážky',
		];

		$form->addSelect('render', 'Vykresľovací stroj:', $renders)
			->setDefaultValue('chart')
			->setPrompt('- Zvoľte spôsob vykreslenia -')
			->setRequired();

		$form->addInteger('vorder', 'Poradie:')
			->setOption('description', 'Poradie v menu - ak je viac grafov s rovnakým tokenom, triedi sa podľa tejto hodnoty. Vyššie číslo = viac hore.'  )
			->setDefaultValue(10)
			->setRequired();

		$form->addSubmit('send', 'Uložiť')
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

		$this->flashMessage("Zmeny prevedené.", 'success');
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
			// toto je overenie práv, preto to tu musí byť
			$this->datasource->getViews( $this->getUser()->id );
			$view = $this->datasource->views[$id];

			$this->datasource->deleteView( $id );
		} 

		$this->flashMessage("Graf zmazaný.", 'success');
		$this->redirect('View:views' );
	}*/

}
