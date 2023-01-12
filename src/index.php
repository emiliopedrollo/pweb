<?php

use App\Application;
use App\Exceptions\Handler;
use App\Request;
use App\Router;

//require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';

$app = new Application(
    base_path: __DIR__
);

$routes = array_flatten(include './routes.php');

$app->bind(Request::class, $request = Request::generate());
$app->bind(Application::class, $app);
$app->bind('routes', $routes);

/** @noinspection PhpUnhandledExceptionInspection */
$app->init();

app(Handler::class)->register();

// todo: boot providers

if (!ob_start("ob_gzhandler")) ob_start();

app(Router::class)->parse($request);

/** @noinspection PhpUnhandledExceptionInspection */
$request->handle();

if (ob_get_status()) {
    echo ob_end_flush();
}
