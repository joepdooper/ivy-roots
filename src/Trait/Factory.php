<?php
namespace Ivy\Trait;

trait Factory
{
    public static function factory(): object
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
        $columns  = $this->getColumns();

        foreach ($defaults as $key => &$value) {
            if (is_object($value) && method_exists($value, 'createFromRequest')) {
                // Create related model and set the foreign key
                $related = $value->createFromRequest();
                $value = $related->getId();
            }
        }

        $existing = [];
        foreach ($columns as $column) {
            if (property_exists($this, $column) && isset($this->$column)) {
                $existing[$column] = $this->$column;
            }
        }

        $filtered = array_intersect_key($data, array_flip($columns));
        $merged = array_merge($defaults, $existing, $filtered);

        $this->populate($merged)->insert();

        return $this;
    }



    public function updateFromRequest(array $data = []): static
    {
        $columns = $this->getColumns();
        $filtered = array_intersect_key($data, array_flip($columns));

        $this->populate($filtered)->update();

        return $this;
    }
}