<?php

namespace App\v1\Handlers;

use App\Model;
use Nette\Database\Table;
use Nette\Security;

use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class DevicesHandler extends Basehandler
{
	/** @var Table\Selection */
	private $devices;

	/** @var Security\User */
	private $user = null;

	public function __construct(Model\Devices $devices, Security\User $user)
	{
		parent::__construct();
		$this->devices = $devices;
		$this->user = $user;
	}

	public function handle(array $params): ResponseInterface
	{
		if (!$this->user->isLoggedIn()) {
			return new JsonApiResponse(401, ['status' => 'error', 'message' => 'Unauthorized! User not logged in...']);
		} else {
			$d = $this->devices->getDevicesUser($this->user->id, true);
			if (count($d) == 0) {
				return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Devices not found!']);
			}
			return new JsonApiResponse(200, ['status' => 'ok', 'units' => $d]);
		}
	}
}
