<?php
declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../../views/{$view}.php";
    }

    protected function json(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}