<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);

// Другие маршруты...