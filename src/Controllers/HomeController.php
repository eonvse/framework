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
            'activePage' => 'home'
        ]);

    }

    public function about(): void
    {
        $this->render('home', [
            'title' => 'About',
            'activePage' => 'about'
        ]);

    }
}