<?php
declare(strict_types=1);

namespace Core;

class Application
{
    public function __construct(
        protected string $rootPath,
        protected ?Router $router = null
    ) {}

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function run(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = $this->normalizeUri($_SERVER['REQUEST_URI']);

            if (!$this->router) {
                throw new \Exception("Router not configured");
            }

            echo $this->router->resolve($method, $uri);
        } catch (\Exception $e) {
            http_response_code($e->getCode());
            echo "Error: " . $e->getMessage();
        }
    }

    protected function stripPublicPrefix(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $publicPath = str_replace($this->rootPath, '', $this->rootPath . '/public');
        return preg_replace("#^{$publicPath}#", '', $uri) ?: '/';
    }

        protected function normalizeUri(string $uri): string
    {
        // Удаляем базовый путь из APP_URL
        $basePath = parse_url($_ENV['APP_URL'], PHP_URL_PATH) ?: '';
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Удаляем /public, если есть
        $publicPath = str_replace($this->rootPath, '', $this->rootPath . '/public');
        $uri = preg_replace("#^{$publicPath}#", '', $uri) ?: '/';
        
        // Удаляем базовый путь APP_URL
        return preg_replace("#^{$basePath}#", '', $uri) ?: '/';
    }

}