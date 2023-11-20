<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('device/<id>[/<action>]', 'Devices:device');
		$router->addRoute('sensor/last/<id>', 'Devices:measureslast');
		$router->addRoute('devices[/<action>[/<id>]]', 'Devices:default');
		$router->addRoute('units[/<action>[/<id>]]', 'Units:default');
		$router->addRoute('user[/<id>[/<action>]]', 'Users:user');
		$router->addRoute('users[/<action>[/<id>]]', 'Users:default');
		$router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
