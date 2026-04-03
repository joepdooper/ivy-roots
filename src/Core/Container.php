<?php

namespace Ivy\Core;

class Container
{
    /** @var array<(callable)|string> */
    protected array $bindings = [];

    /** @var string[] */
    protected array $instances = [];

    public function bind(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, callable $concrete): void
    {
        $this->instances[$abstract] = $concrete($this);
    }

    public function get(string $abstract): string
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (! isset($this->bindings[$abstract])) {
            throw new \Exception("Nothing bound to {$abstract}");
        }

        return $this->bindings[$abstract]($this);
    }
}
