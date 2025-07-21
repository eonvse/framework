<?php
declare(strict_types=1);

namespace Core;

class Container
{
    protected array $bindings = [];

    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    public function resolve(string $key): mixed
    {
        return $this->bindings[$key]();
    }
}