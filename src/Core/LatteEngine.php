<?php
declare(strict_types=1);

namespace Core;

use Latte\Engine;

class LatteEngine
{
    private Engine $latte;

    public function __construct(string $viewsPath, Application $app)
    {
        $this->latte = new Engine();
        $this->latte->setTempDirectory(__DIR__ . '/../../storage/cache/latte');
        $this->latte->setAutoRefresh(true); // false в production
        
        // Устанавливаем путь к шаблонам
        $this->latte->setLoader(new \Latte\Loaders\FileLoader($viewsPath));

        // Регистрация компонентов
        $this->latte->addProvider('core', $this);

        // Регистрируем стандартные фильтры
        $this->latte->addFilter('default', function($value, $default = '') {
            return $value ?? $default;
        });

        // Другие полезные фильтры
        $this->latte->addFilter('ucfirst', 'ucfirst');
        $this->latte->addFilter('date', function($timestamp, $format = 'Y-m-d') {
            return date($format, is_numeric($timestamp) ? $timestamp : strtotime($timestamp));
        });

        $router = $app->getRouter();
        $this->latte->addFilter('route', function(string $name, array $params = []) use ($router) {
            return $router->route($name, $params);
        });
    }

    public function render(string $path, array $params = []): string
    {
        return $this->latte->renderToString($path, $params);
    }

}