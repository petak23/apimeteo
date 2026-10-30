<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Tester\Assert;
use Tester\TestCase;
use Tester\HttpAssert;
use App\Services;

require __DIR__ . '/bootstrap.php';

class CommPresenterTest extends TestCase
{
	/** @var \App\Presenters\CommPresenter */
	private $presenter;

	protected function setUp(): void
	{
		$this->presenter = new \App\Presenters\CommPresenter();
	}


	public function testDefaultAction()
	{
		$response = HttpAssert::fetch('http://localhost/apimeteo/comm');
		$response
			->expectCode(200)
			->expectHeader('Content-Type', contains: 'json')
			->expectBody(contains: '"status":200');
	}

	public function testLoginAction()
	{
		$data = [
			'login_time' => '30.10.2026 07:50:00',
			'comm_key' => hash('sha256', 'AA:testdevice' . "Ka5t_Qu1646" . '30.10.2026 07:50:00' . 'TestApp'),
			'device_name' => 'AA:testdevice',
			'appname' => 'TestApp',
			"rssi"=>-88,
			"uptime"=>"2"
		];

		$response = HttpAssert::fetch(
			'http://localhost/apimeteo/comm/login',
			method: 'POST',
    	headers: [
        'Accept: application/json',
    	],
    	body: json_encode($data)
		);
		$response
			->expectCode(200)
			->expectHeader('Content-Type', contains: 'json')
			->expectBody(contains: '"status":200');
	}
}

(new CommPresenterTest())->run();