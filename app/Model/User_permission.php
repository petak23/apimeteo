<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database;

/**
 * Model starajuci sa o tabulku user_permission
 * 
 * Posledna zmena 29.01.2026
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2026 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.3
 */
class User_permission extends Table
{
	/** @var string */
	protected $tableName = 'user_permission';

	public function check(int $id_user_role = 1, String $resource = "Homepage:"): bool
	{
		$t = $this->findOneBy(["id_user_roles <= " => $id_user_role, "user_resource.name" => $resource]);
		return $t != null;
	}

		/** 
	 * Hlada urovne registracie uzivatela v rozsahu od do */
	public function getAllowedPermission(int $id_user_roles = 0, bool $return_as_array = false): Database\Table\Selection|array
	{
		//dump($id_user_roles);
		$out = $this->findBy(['id_user_roles <= ' . $id_user_roles]);
		if ($return_as_array) {
			$_tmp = [];
			foreach ($out as $p) {
				//dump($p);
				$ov = 0;
				foreach ($_tmp as $k => $v) {
					if ($v['resource'] == $p->user_resource->name && $v['id_user_roles'] < $p->id_user_roles)
						$ov = $k;
				}
				if ($ov) {
					$_tmp[$ov] = [
						'resource' => $p->user_resource->name,
						'action' => $p->actions != null ? explode(",", $p->actions) : null,
						'id_user_roles' => $p->id_user_roles,
					];
				} else {
					$_tmp[] = [
						'resource' => $p->user_resource->name,
						'action' => $p->actions != null ? explode(",", $p->actions) : null,
						'id_user_roles' => $p->id_user_roles,
					];
				}
			}
			$out = $_tmp;
		}
		//dumpe($out);
		return $out;
	}
}
