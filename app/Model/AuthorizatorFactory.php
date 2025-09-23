<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Autorizator
 * 
 * Posledna zmena(last change): 23.09.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.5
 */
class AuthorizatorFactory
{
	use Nette\SmartObject;

	const
		TABLE_NAME_ROLES = 'user_roles',
		TABLE_NAME_RESOURCE = 'user_resource',
		TABLE_NAME_PERMISSION = 'user_permission',
		// Mandatory columns for table TABLE_NAME_ROLES
		COLUMN_ROLE = 'role',
		COLUMN_INHERITED = 'inherited', //Dedi od... 
		// Mandatory columns for table TABLE_NAME_RESOURCE
		COLUMN_RESOURCE_NAME = 'name',
		// Mandatory columns for table TABLE_NAME_PERMISSION
		COLUMN_RESOURCE_ACTIONS = 'actions';

	public static function create(Nette\Database\Explorer $database): Nette\Security\Permission
	{
		$acl = new Nette\Security\Permission;

		$roles = $database->table(self::TABLE_NAME_ROLES);
		foreach ($roles as $role) {
			$acl->addRole($role->{self::COLUMN_ROLE}, $role->{self::COLUMN_INHERITED});
		}

		$resource = $database->table(self::TABLE_NAME_RESOURCE);
		foreach ($resource as $res) {
			$acl->addResource($res->{self::COLUMN_RESOURCE_NAME});
		}

		$permission = $database->table(self::TABLE_NAME_PERMISSION);
		foreach ($permission as $perm) {
			if ($perm->{self::COLUMN_RESOURCE_ACTIONS} !== NULL) {
				$acl->allow(
					$perm->{self::TABLE_NAME_ROLES}->{self::COLUMN_ROLE},
					$perm->{self::TABLE_NAME_RESOURCE}->{self::COLUMN_RESOURCE_NAME},
					explode(',', $perm->{self::COLUMN_RESOURCE_ACTIONS})
				);
			} else {
				$acl->allow(
					$perm->{self::TABLE_NAME_ROLES}->{self::COLUMN_ROLE},
					$perm->{self::TABLE_NAME_RESOURCE}->{self::COLUMN_RESOURCE_NAME},
					Nette\Security\Permission::All
				);
			}
		}

		return $acl;
	}
}
