<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Tester\Assert;
use Tester\TestCase;
use Tester\HttpAssert;

require __DIR__ . '/bootstrap.php';

class UnitsPresenterTest extends TestCase
{
	/** @var \App\Presenters\UnitsPresenter */
	private $presenter;

	protected function setUp(): void
	{
		$this->presenter = new \App\Presenters\UnitsPresenter();
	}

	public function testDefaultAction()
	{
		$response = HttpAssert::fetch('http://localhost/apimeteo/units');
		$response
			->expectCode(200)
			->expectHeader('Content-Type', contains: 'json')
			->expectBody(contains: 'data');
	}
}

(new UnitsPresenterTest())->run();