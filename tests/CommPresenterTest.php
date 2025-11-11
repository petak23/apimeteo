<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

//use Tester\Assert;
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
			'device_name' => 'AA:testdevice',
			'login_time' => '2025-11-11 12:00:00',
			'appname' => 'TestApp',
			'payload_hash' => hash('sha256', 'PV:testdevice' . "Ka5t_Qu1646" . '2025-11-11 12:00:00' . 'TestApp'),
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