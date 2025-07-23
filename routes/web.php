<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\UserController;
use Core\Router;
use App\Models\User;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index'], 'home');
$router->get('/about', [HomeController::class, 'about'], 'about');

$router->get('/user/{id}', [UserController::class,'show'], 'user show');

$router->get('/db-test', function() {
    $users = User::all();
    print_r($users);
});

$router->get('/db-check', function() {
    try {
        $pdo = Core\Database\Connection::getInstance()->getPdo();
        $stmt = $pdo->query('SELECT 1');
        return "Database connection successful!";
    } catch (\Exception $e) {
        return "Database error: " . $e->getMessage();
    }
});