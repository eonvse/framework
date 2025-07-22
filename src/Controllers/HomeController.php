<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home', [
            'title' => 'Главная страница',
            'showButton' => true
        ]);

    }

    public function about(): void
    {
        $this->json(['message' => 'About Page']);
    }
}