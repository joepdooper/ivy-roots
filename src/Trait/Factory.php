<?php
namespace Ivy\Trait;

trait Factory
{
    public function factory(): object
    {
        $factoryClass = static::class . 'Factory';

        if (!class_exists($factoryClass)) {
            throw new \RuntimeException("Factory not found for " . static::class);
        }

        return new $factoryClass();
    }

    public function createFromRequest(array $data = []): static
    {
        $factory = $this->factory();

        if (!method_exists($factory, 'defaults')) {
            throw new \RuntimeException("Factory for " . static::class . " must define defaults()");
        }

        $defaults = $factory->defaults();
        $columns = $this->getColumns();

        $merged = array_merge($defaults, array_intersect_key($data, array_flip($columns)));

        $this->populate($merged);

        return $this;
    }

    public function updateFromRequest(array $data = []): static
    {
        $factory = $this->factory();
        $columns = $this->getColumns();
        $filtered = array_intersect_key($data, array_flip($columns));

        $this->populate($filtered);

        return $this;
    }
}