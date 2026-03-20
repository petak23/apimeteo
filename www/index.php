<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
/* - old index.php -

$configurator = App\Bootstrap::boot();
$container = $configurator->createContainer();
$application = $container->getByType(Nette\Application\Application::class);
$application->run();
*/

$isLocalhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'], true);

$allowedOrigins = $isLocalhost
    ? ['http://localhost:5173']
    : ['https://vuemeteo.echo-msz.eu'];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    http_response_code(204);
    exit();
}

$app = App\Bootstrap::boot();
$container = $app->createContainer();

// Tu pridáš CORS hlavičky – pred spustením aplikácie:
$application = $container->getByType(Nette\Application\Application::class);
$httpResponse = $container->getByType(Nette\Http\Response::class);

//$allowedOrigin = 'http://localhost:5173';

$application->onResponse[] = function ($app, $response) use ($httpResponse, $allowedOrigins) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins, true)) {
        $httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
    }
    $httpResponse->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $httpResponse->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    $httpResponse->setHeader('Access-Control-Allow-Credentials', 'true');
};


// Spustenie aplikácie
$application->run();
