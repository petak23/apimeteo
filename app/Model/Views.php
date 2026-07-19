<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database;

/**
 * Model, ktorý sa stará o tabuľku views
 * 
 * Posledná zmena 17.07.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class Views extends Table
{
	protected string $tableName = 'views';

	public Database\Table\Selection $view_detail;

	/**
	 * @param Database\Explorer $db */
	public function __construct(Database\Explorer $db)
	{
		parent::__construct($db);
		$this->view_detail = $db->table("view_detail");
	}

	//public $views;
	//public $tokens;
	//public $tokenView;

	public function readViews(int $userId): array
	{
		$views_new = [];
		$tokenView = [];
		$tokens = [];

		// nacteme pohledy
		$result = $this->findBy(["user_id" => $userId])->order("token ASC, vorder DESC");

		foreach ($result as $viewMeta) {
			$v_new = $viewMeta->toArray();
			$views_new[$viewMeta->id] = $v_new;

			if (!isset($tokens[$v_new["token"]])) {
				$tokens[$v_new["token"]] = $v_new["token"];
			}
			$v_new["items"] = [];
			$tokenView[$v_new["token"]] = isset($tokenView[$v_new["token"]]) ? $tokenView[$v_new["token"]] : [$v_new];
		}

		// a k nim nacteme polozky
		$result = $this->view_detail
			->where("view.user_id", $userId)
			->order("id_view ASC, vorder ASC");

		foreach ($result as $row) {
			
			$vi = new ViewItem();
			$vi->vorder = $row->vorder;
			$vi->axisY = $row->y_axis;
			$vi->source = $row->id_view_source;
			$vi->sourceDesc = $row->view_source->short_desc;
			$vi->setColor(1, $row->color_1, true);
			$vi->setColor(2, $row->color_2, true);
			$vi->id = $row->id;

			$sids = explode(',', $row->sensor_ids);
			$vi->sensorIds = $sids;
			$vi_a = $vi->toArray();
			$views_new[$row->id_view]['items'][] = $vi_a;
			$tokenView[$row->view->token][0]['items'][] = $vi_a;
		}
		
		return [
			"views" => $views_new,
			"tokens" => $tokens,
			"tokenView" => $tokenView,
		];
	}

	/*public function toArray() : array {
		$out = [];
		foreach ($this->views as $k => $v) {
			$out[$k] = [
				"name" => $v->name,
				"appName" => $v->appName,
				"desc" => $v->desc,
				"allowCompare" => $v->allowCompare,
				"items"	=> $v->items,
				"token" => $v->token,
				"vorder" => $v->vorder,
				"render" => $v->render,
				"id" => $v->id,
			];
		}
		return $out; 
	}*/

	public function getAllForForm(): array
	{
		return $this->findAll()->fetchPairs('id', 'desc');
	}

	public function deleteViewsForUser(int $id)
	{
		$views = $this->findBy(["user_id" => $id]);

		$this->view_detail->where("view_id", $views->select("id"));
		$views->delete();
	}
}