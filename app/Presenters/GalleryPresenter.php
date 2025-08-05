<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model;
use Nette;
use Nette\Utils\Strings;
use Nette\Application\Responses\FileResponse;

/**
 * Last change 27.07.2023
 * 
 * @github     Forked from petrbrouzda/RatatoskrIoT
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2021 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.2
 */
final class GalleryPresenter extends BasePresenter
{
	use Nette\SmartObject;

	// Database tables
	/** @var Model\PV_Blobs @inject */
	public $blobs;
	/** @var Model\PV_Devices @inject */
	public $devices;

	// hodnoty z konfigurace
	private $fontName;
	private $fontNameBold;
	private $dataRetentionDays;
	private $appName;
	private $minYear;

	/** @persistent */
	public $filterBlack = 1;

	public function __construct(\App\Services\Config $config)
	{
		$this->links = $config->links;
		$this->appName = $config->appName;
	}


	private $dny = [' ', 'po', 'út', 'st', 'čt', 'pá', 'so', 'ne'];

	private function hezkeDatum($date, $format)
	{
		$today = new Nette\Utils\DateTime();
		$dateT = $date->format('Y-m-d');

		if (strcmp($today->format('Y-m-d'), $dateT) == 0) {
			return "dnes " . $date->format($format);
		}

		if (strcmp($today->modifyClone('-1 day')->format('Y-m-d'), $dateT) == 0) {
			return "včera " . $date->format($format);
		}

		return $this->dny[$date->format('N')] . ' ' . $date->format($format);
	}

	// https://lovecka.info/raT/gallery/aabbcc/12/
	public function renderShow(int $id, string $token)
	{
		$device = $this->devices->getDevice($id);
		if (!$device) {
			$this->error('Zařízení nebylo nalezeno');
		}
		if (!$token || ($device['blob_token'] !== $token)) {
			$this->error('Token nesouhlasí.');
		}

		$this->template->links = $this->links;
		$this->template->maxW = 1800;

		$blobs = $this->blobs->getBlobs($id);

		$dates = array();
		$outBlobs = array();

		$prevDate = 'aaa';
		foreach ($blobs as $blob) {

			if (
				$blob['extension'] === 'jpg'
				&& $this->filterBlack == 1
				&& (!strpos($blob['description'], 'BLACK') === FALSE)
			) {
				// celocerne fotky preskocime
				continue;
			}
			$data_time = $blob['data_time'];
			$blob['tst'] = $this->hezkeDatum($blob['data_time'], 'd.m.') . ' ' . $blob['data_time']->format('H:i');
			$blobDate = $data_time->format('Y-m-d');
			if ($blobDate == $prevDate) {
				$blob['date_change'] = FALSE;
			} else {
				$blob['date_change'] = TRUE;
				$prevDate = $blobDate;
				$date = $this->hezkeDatum($blob['data_time'], 'd.m.Y');
				$blob['href'] = $date;
				$dates[] = $date;
			}

			$outBlobs[] = $blob;
		}

		$this->template->countAll = count($blobs);
		$this->template->countFiltered = count($outBlobs);


		$this->template->dates = $dates;
		$this->template->blobs = $outBlobs;

		$this->template->appName = $device['desc'];

		$this->template->id = $id;
		$this->template->token = $token;

		$this->template->filterBlack = $this->filterBlack;

		$this->template->device = $device;
		$this->template->loadTime = time();
		$this->template->refreshTime = $this->template->loadTime + 300;

		$response = $this->getHttpResponse();
		$response->setHeader('Pragma', 'public');
		$response->setHeader('Cache-Control', '');
		$response->setExpiration('1 min');
	}

	public function renderBlob(int $id, string $token, int $blobid): void
	{
		$etag = "RA-img-{$id}-{$blobid}";

		$httpRequest = $this->getHttpRequest();
		$response = $this->getHttpResponse();
		$hdr = $httpRequest->getHeader('If-None-Match');
		if (isset($hdr) && $hdr == $etag) {
			// vratit, ze je vse beze zmeny
			$response->setCode(Nette\Http\Response::S304_NotModified);
			$this->terminate();
			return;
		}

		$device = $this->devices->getDevice($id);
		if (!$device) {
			$this->error('Zařízení nebylo nalezeno');
		}
		if (!$token || ($device['blob_token'] !== $token)) {
			$this->error('Token nesouhlasí.');
		}

		$blob = $this->blobs->getBlob($id, $blobid);
		if (!$blob) {
			// $this->error('Soubor nenalezen nebo k němu nejsou práva.', Nette\HTTP\Response::S404_NotFound );
			$response->setCode(Nette\Http\Response::S404_NotFound);
			$this->terminate();
			return;
		}

		$fileName =
			$blob['data_time']->format('Ymd_His') .
			"_{$id}_" .
			Strings::webalize($blob['description'], '._') .
			".{$blob['extension']}";


		$contentType = 'application/octet-stream';
		if ($blob['extension'] == 'jpg') {
			$contentType = 'image/jpeg';
		}

		$file = __DIR__ . "/../../data/" . $blob['filename'];
		if (!file_exists($file)) {
			$this->template->info = "Soubor uz neexistuje";
			return;
		}
		$rsp = new FileResponse($file, $fileName, $contentType, FALSE);

		$response->setHeader('Pragma', '');
		$response->setHeader('Cache-Control', 'public');
		$response->setHeader('ETag', $etag);
		$response->setExpiration('10 day');

		$this->sendResponse($rsp);
	}
}
