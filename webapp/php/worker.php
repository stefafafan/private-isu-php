<?php

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use App\Dependencies;

$container = Dependencies::initializeContainer();
$container = Dependencies::setupRoutes($container);

$worker = Worker::create();
$factory = new Psr17Factory();
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

$app = $container->get('app');

while (true) {
    try {
        $request = $psr7->waitRequest();
        if ($request === null) {
            break;
        }
    } catch (\Throwable $e) {
        $psr7->respond(new Response(400));
        continue;
    }

    try {
        $response = $app->handle($request);
        $psr7->respond($response);
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], (string)$e));
        $psr7->getWorker()->error((string)$e);
    }
}
