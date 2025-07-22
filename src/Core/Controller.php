<?php
declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    protected function render(string $view, array $data = []): void
    {
        try {
            $latte = new LatteEngine($this->app->getViewsPath());
            // Убедимся, что $core передается
            $data['core'] = $latte;
            echo $latte->render("pages/{$view}.latte", $data);
        } catch (\Exception $e) {
            throw new \Exception("Template rendering failed: " . $e->getMessage());
        }
    }

    protected function component(string $name, array $props = []): string
    {
        $latte = new LatteEngine($this->app->getViewsPath());
        return $latte->render("components/{$name}.latte", $props);
    }

    protected function json(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}