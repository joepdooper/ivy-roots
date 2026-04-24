<?php

namespace Ivy\Core;

class Container
{
    /** @var array<string, callable> */
    protected array $bindings = [];

    /** @var array<string, mixed> */
    protected array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = function ($container) use ($factory, $abstract) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($container);
            }
            return $this->instances[$abstract];
        };
    }

    public function get(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException("Service not found: {$abstract}");
    }
}