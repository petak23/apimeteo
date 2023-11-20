<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Model starajuci sa o tabulku user_permission
 * 
 * Posledna zmena 28.09.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.1
 */
class User_permission extends Table
{
	/** @var string */
	protected $tableName = 'user_permission';

	public function check(int $id_user_role = 1, String $resource = "Homepage:"): bool
	{
		$t = $this->findOneBy(["id_user_roles <= " . $id_user_role, "user_resource.name" => $resource]);
		return ($t != null);
	}
}
