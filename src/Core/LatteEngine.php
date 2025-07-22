<?php
declare(strict_types=1);

namespace Core;

use Latte\Engine;

class LatteEngine
{
    private Engine $latte;

    public function __construct(string $viewsPath)
    {
        $this->latte = new Engine();
        $this->latte->setTempDirectory(__DIR__ . '/../../storage/cache/latte');
        $this->latte->setAutoRefresh(true); // false в production
        
        // Устанавливаем путь к шаблонам
        $this->latte->setLoader(new \Latte\Loaders\FileLoader($viewsPath));

        // Регистрация компонентов
        $this->latte->addProvider('core', $this);
        $this->latte->addFilter('ucfirst', 'ucfirst');
    }

    public function render(string $path, array $params = []): string
    {
        return $this->latte->renderToString($path, $params);
    }

}