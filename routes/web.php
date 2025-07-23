<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use Core\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index'], 'home');
$router->get('/about', [HomeController::class, 'about'], 'about');

// Другие маршруты...