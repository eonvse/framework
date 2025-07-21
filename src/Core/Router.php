<?php
declare(strict_types=1);

namespace Core;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $uri, callable|array $action): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'action' => $action,
        ];
        return $this;
    }

    public function get(string $uri, callable|array $action): self
    {
        return $this->add('GET', $uri, $action);
    }

    public function post(string $uri, callable|array $action): self
    {
        return $this->add('POST', $uri, $action);
    }

    public function resolve(string $method, string $uri): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {
                return $this->callAction($route['action']);
            }
        }

        throw new \Exception("Route not found: {$method} {$uri}", 404);
    }

    protected function callAction(callable|array $action): mixed
    {
        if (is_callable($action)) {
            return $action();
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            if (class_exists($controller) && method_exists($controller, $method)) {
                return (new $controller)->{$method}();
            }
        }

        throw new \Exception("Invalid route action", 500);
    }
}