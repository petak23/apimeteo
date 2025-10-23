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
		$router->addRoute('sensor/<id>', 'Devices:sensor');
		$router->addRoute('devices[/<action>[/<id>]]', 'Devices:default');
		$router->addRoute('units[/<action>[/<id>]]', 'Units:default');
		$router->addRoute('comm[/action[/id]]', 'Comm:default');
		$router->addRoute('login', 'Users:logIn');
		$router->addRoute('logout', 'Users:logOut');
		//$router->addRoute('user/save/<id>', 'Users:save');
		//$router->addRoute('user/passwordchange/<id>', 'Users:passwordChange');
		$router->addRoute('user[/<action>[/<id>]]', 'Users:user');
		$router->addRoute('users[/<action>[/<id>]]', 'Users:default');
		$router->addRoute('json[/<action>[/<id>/<token>]]', 'Json:default');
		$router->addRoute('monitor/show/<token>/<id>/', 'Monitor:show');
		$router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}
}
