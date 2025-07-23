<?php
declare(strict_types=1);

namespace Core;

use Core\Http\Response;
use Core\Http\HttpError;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected Response $response;

    public function __construct(
        protected Application $app
    ) {
        $this->response = new Response();
    }

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

        // Удаляем оставшиеся необязательные параметры
        $uri = preg_replace('/\/\{[^}]+\}/', '', $uri);
        
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
            // Преобразуем URI маршрута в регулярное выражение
            $pattern = $this->buildRoutePattern($route['uri']);
            
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // Извлекаем параметры из URI
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->callAction($route['action'], $params);
            }
        }

        return $this->response
                    ->setStatusCode(404)
                    ->setContent("Route not found: {$method} {$uri}");;
    }

    protected function buildRoutePattern(string $uri): string
    {
        // {param} обязательный параметр
        // {param?} необязательный параметр
        // Для числовых параметров {id} используем \d+
        $pattern = preg_replace('/\{([a-z]+):int\}/', '(?P<$1>\d+)', $uri);
        // Для строковых параметров {slug}
        $pattern = preg_replace('/\{([a-z]+):string\}/', '(?P<$1>[^/]+)', $pattern);
        // Для обычных параметров
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $pattern);
        
        return '#^' . $pattern . '$#i';
    }

    protected function callAction(callable|array $action, array $params = []): mixed
    {
        if (is_callable($action)) {
            $result = $action(...$params);
            return $this->ensureResponse($result);
        }

        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;
            
            if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
                $controller = new $controllerClass($this->app); // Передаем app в контроллер
                return $this->callMethod($controller, $method, $params);
            }
        }

        throw new \Exception("Invalid route action", 500);
    }

    protected function ensureResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        return $this->response->setContent((string)$result);
    }

    protected function callMethod(object $controller, string $method, array $params): mixed
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $arguments = $this->resolveMethodParameters($reflection, $params);
        
        $result = $controller->$method(...$arguments);
        
        if ($result instanceof Response) {
            return $result;
        }

        return $this->response->setContent($result);
    }

    /**
     * Разрешает параметры метода контроллера на основе переданных значений и типов
     */
    protected function resolveMethodParameters(\ReflectionMethod $method, array $routeParams): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // Получаем значение из маршрута или запроса
            $value = $this->getParameterValue($param, $paramName, $routeParams);

            // Приводим тип, если он указан
            $parameters[] = $this->castParameterValue($value, $paramType, $param);
        }

        return $parameters;
    }

    /**
     * Получает значение параметра из разных источников
     */
    protected function getParameterValue(
        \ReflectionParameter $param,
        string $paramName,
        array $routeParams
    ): mixed {
        // 1. Проверяем параметры маршрута (/user/{id})
        if (array_key_exists($paramName, $routeParams)) {
            return $routeParams[$paramName];
        }

        // 2. Проверяем GET/POST параметры
        $request = $this->app->getRequest();
        if ($request->has($paramName)) {
            return $request->input($paramName);
        }

        // 3. Проверяем значение по умолчанию
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new \RuntimeException("Missing required parameter: {$paramName}");
    }

    /**
     * Приводит значение к нужному типу
     */
    protected function castParameterValue(
        mixed $value,
        ?\ReflectionType $type,
        \ReflectionParameter $param
    ): mixed {
        if ($type === null) {
            return $value;
        }

        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            switch ($typeName) {
                case 'int':
                    if (!is_numeric($value)) {
                        throw HttpError::invalidArgument("Parameter must be integer");
                    }
                    return (int)$value;
                case 'float':
                    if (!is_numeric($value)) {
                        throw HttpError::invalidArgument("Parameter must be float");
                    }
                    return (float)$value;
                case 'bool':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'string':
                    return (string)$value;
                default:
                    return $value;
            }
        }

        // Для объектов пробуем создать экземпляр через DI контейнер
        if (class_exists($typeName)) {
            return $this->app->make($typeName);
        }

        throw new \RuntimeException("Cannot resolve parameter {$param->getName()} of type {$typeName}");
    }
}