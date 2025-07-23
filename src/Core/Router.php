<?php
declare(strict_types=1);

namespace Core;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];

    public function __construct(
        protected Application $app
    ) {}

    public function add(string $method, string $uri, callable|array $action, ?string $name = null): self
    {
        $route = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'action' => $action,
            'name' => $name
        ];

        $this->routes[] = $route;
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $this;
    }

    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route with name '{$name}' not found");
        }

        $route = $this->namedRoutes[$name];
        $uri = $_ENV['APP_URL'] . $route['uri'];
        
        // Замена параметров {param} в URI
        foreach ($params as $key => $value) {
            $uri = str_replace('{'.$key.'}', (string)$value, $uri);
        }
        
        return $uri;
    }


    public function get(string $uri, callable|array $action, ?string $name = null): self
    {
        return $this->add('GET', $uri, $action, $name);
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
            [$controllerClass, $method] = $action;
            
            if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
                $controller = new $controllerClass($this->app); // Передаем app в контроллер
                return $controller->{$method}();
            }
        }

        throw new \Exception("Invalid route action", 500);
    }
}