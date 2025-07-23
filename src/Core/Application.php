<?php
declare(strict_types=1);

namespace Core;

use Core\Http\Response;
use Core\Exceptions\HttpException;
use Dotenv\Dotenv;

class Application
{
    public function __construct(
        protected string $rootPath,
        protected ?Router $router = null
    ){
        $this->setupPaths();
        $this->router = $this->router ?? new Router($this);
        $this->loadEnvironment();
        $this->initializeDatabase();

    }

    protected function setupPaths(): void
    {
        define('TEMPLATES_PATH', $this->rootPath . '/views');
        define('STORAGE_PATH', $this->rootPath . '/storage');
    }
    protected function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->rootPath);
        $dotenv->safeLoad();
        
        // Обязательные переменные
        $dotenv->required([
            'DB_DRIVER',
            'DB_HOST',
            'DB_DATABASE'
        ]);
        
        // Переменные с значениями по умолчанию
        $_ENV['DB_PORT'] = $_ENV['DB_PORT'] ?? '3306';
        $_ENV['DB_CHARSET'] = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        $_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'root';
        $_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';
    }

    protected function initializeDatabase(): void
    {
        Database::getPdo(); // Инициализация подключения
    }

    public function getViewsPath(): string
    {
        return TEMPLATES_PATH;
    }

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function getRouter(): Router
    {
        if (!$this->router) {
            throw new \RuntimeException('Router has not been initialized');
        }
        return $this->router;
    }

    public function run(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = $this->normalizeUri($_SERVER['REQUEST_URI']);

            if (!$this->router) {
                throw new \Exception("Router not configured");
            }

            $response = $this->router->resolve($method, $uri);

            // Обрабатываем Response и строки
            if ($response instanceof Response) {
                $response->send();
            } elseif (is_scalar($response) || (is_object($response) && method_exists($response, '__toString'))) {
                echo $response;
            } else {
                throw new \RuntimeException("Invalid response type");
            }
        } catch (HttpException $e) {
            $e->getResponse()->send();
        } catch (\Throwable $e) {
            Response::error('Server Error: '.$e->getMessage().' '.$e->getFile().' '.$e->getLine(), 500)->getResponse()->send();
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

    public function getDatabasePath(): string
    {
        return $this->rootPath . '/database';
    }

}