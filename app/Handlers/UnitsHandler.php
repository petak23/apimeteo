<?php

namespace App\v1\Handlers;

use App\Model;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UnitsHandler extends Basehandler
{
	private $units;

	public function __construct(Model\Units $units)
	{
		parent::__construct();
		$this->units = $units;
	}

	public function handle(array $params): ResponseInterface
	{
		$u = $this->units->getUnits();
		if (count($u) == 0) {
			return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Units not found']);
		}
		return new JsonApiResponse(200, ['status' => 'ok', 'units' => $u]);
	}
}
