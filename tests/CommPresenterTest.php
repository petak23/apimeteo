<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Model\PV_Sessions;
use Tester\Assert;
use Tester\TestCase;
use Tester\HttpAssert;
use App\Model;
use Nette\Utils\DateTime;

require __DIR__ . '/bootstrap.php';

class CommPresenterTest extends TestCase
{
	/** @var \App\Presenters\CommPresenter */
	private $presenter;

	/** @var Model\PV_Sessions @inject */
	private $session;

	protected function setUp(): void
	{
		$this->presenter = new \App\Presenters\CommPresenter();
		//$this->session = new Model\PV_Sessions();
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
		$_now = DateTime::from(null)->format('d.M.Y H:i:s');
		$data = [
			'login_time' => $_now,
			'comm_key' => hash('sha256', 'AA:testdevice' . "Ka5t_Qu1646" . $_now . 'TestApp'),
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

	/** Incoming data of dataJson: 
	 *{"sensors":
	 *	[
	 *		{"id":"te","raw_value":"6.58","id_device_classes":1,"id_value_types":1,"preprocess_factor":null},
	 *		...
	 *	],
	 *	"last_measure":"30.10.2026 14:05:00",
	 *	"priority":1,
	 *	"data_length":947,
	 *	"payload_hash":"72048e5d9caa69262d6c82022996bed55d9206028282512a3c91e41337e56898",
	 *	"log":"[30.10.2026 14:00:00] Meranie senzorov ukončené\n[30.10.2026 14:00:01] Odoslanie POST na https://apimeteo.echo-msz.eu/comm/datajson\n[30.10.2026 14:00:06] Meranie bolo úspešne odoslané\n[30.10.2026 14:05:00] Vietor: impulzy=2073, čas=300.0 s, rýchlosť=4.61 m/s\n[30.10.2026 14:05:00] Začínam meranie senzorov...\n[30.10.2026 14:05:00] BMx: Teplota = 6.58 °C\n[30.10.2026 14:05:00] BMx: Absolutny tlak = 589.3 hPa\n[30.10.2026 14:05:00] BMx: Relativny tlak = 640.5 hPa\n[30.10.2026 14:05:00] BMx: Nadmorska vyska =  677 m.n.m\n[30.10.2026 14:05:00] Abs tlak: 589.33 hPa\n[30.10.2026 14:05:00] Intenzita osvetlenia = 2445.83 Lx\n[30.10.2026 14:05:00] Teplota pri zemi: 7.00 °C\n",
	 *	"app_name":"MeteoZahradka v.1.3.5 (29.10.2026)",
	 *	"device_name":"PV:meteozahradka",
	 *	"session_id":1108,
	 *	"session_hash":"sJEdw1fm",
	 *	"rssi":-88,
	 *	"uptime":"50296"}
	 */
	public function testDatajsonAction()
	{
		$_now = DateTime::from(null)->format('d.M.Y H:i:s');
		$_sess = $this->session->findOneBy(["device_id"=>4]);
		$data = [
			"sensors" =>
	 			[
	 				"id"		=> "te_test",
	 				"raw_value"	=> "6.58",
					"id_device_classes"	=> 1,
					"id_value_types"	=> 1,
					"preprocess_factor"	=> null,
	 		],
			"last_measure"	=> $_now,
			"priority"	=> 1,
	 		"data_length" => 947,
	 		"payload_hash" => hash('sha256', 'AA:testdevice' . "Ka5t_Qu1646" . $_now . 'TestApp'), //todo
	 		"log"	=> "Test data send",
			"app_name"	=> "TestApp",
	 		"device_name"	=> "AA:testdevice",
	 		"session_id"	=> $_sess->id,
	 		"session_hash"	=> $_sess->hash,
	 		"rssi"	=> -88,
	 		"uptime"	=> "50296",
		];

		$response = HttpAssert::fetch(
			'http://localhost/apimeteo/comm/datajson',
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