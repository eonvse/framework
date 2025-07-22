<?php
declare(strict_types=1);

use Core\Application;
use Core\Router;
use Dotenv\Dotenv;
use App\Controllers\HomeController;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$app = new Application(dirname(__DIR__));

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->get('/about', [HomeController::class, 'about']);

$app->setRouter($router);