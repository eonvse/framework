<?php
declare(strict_types=1);

namespace Core;

use Core\Http\Response;

abstract class Controller
{
    protected Response $response;

    public function __construct(protected Application $app)
    {
        $this->app = $app;
        $this->response = new Response();
    }
    
    protected function render(string $view, array $data = []): void
    {
        try {
            $latte = new LatteEngine(
                $this->app->getViewsPath(),
                $this->app
            );
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

    protected function json(array $data, int $status = 200): Response
    {
        return $this->response
            ->setStatusCode($status)
            ->json($data);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return $this->response->redirect($url, $status);
    }

    protected function view(string $path, array $data = []): Response
    {
        $latte = new LatteEngine(
            $this->app->getViewsPath(),
            $this->app
        );
        
        $data['core'] = $latte;
        $content = $latte->render("pages/{$path}.latte", $data);
        
        return $this->response
            ->header('Content-Type', 'text/html')
            ->setContent($content);
    }

}