<?php
declare(strict_types=1);

use Core\Application;
use Core\Router;

$app = new Application(dirname(__DIR__));

$router = new Router();

// Тестовые маршруты (будут работать относительно `/public`)
$router->get('/', function () {
    echo "Home Page (URL: /)";
});

$router->get('/about', function () {
    echo "About Page (URL: /about)";
});

$app->setRouter($router);