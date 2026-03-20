<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;


/**
 * Handles uncaught exceptions and errors, and logs them.
 */
final class ErrorPresenter implements Nette\Application\IPresenter
{
	public function __construct(
		private ILogger $logger,
	) {
	}


	public function run(Nette\Application\Request $request): Nette\Application\Response
	{
		$exception = $request->getParameter('exception');

		// If the exception is a 4xx HTTP error, forward to the Error4xxPresenter
		if ($exception instanceof Nette\Application\BadRequestException) {
			[$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
			return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
		}

		// Log the exception and display a generic error message to the user
		$this->logger->log($exception, ILogger::EXCEPTION);
		return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			$allowedOrigins = ['https://vuemeteo.echo-msz.eu', 'http://localhost:5173'];
			$origin = $httpRequest->getHeader('Origin');
			if ($origin && in_array($origin, $allowedOrigins, true)) {
				$httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
			} else {
				$httpResponse->setHeader('Access-Control-Allow-Origin', $allowedOrigins[0]);
			}
			$httpResponse->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
			$httpResponse->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
			$httpResponse->setHeader('Access-Control-Allow-Credentials', 'true');

			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
				require __DIR__ . '/../templates/Error/500.phtml';
			}
		});
	}
}
