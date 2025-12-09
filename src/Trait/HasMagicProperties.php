<?php

namespace Ivy\Trait;

trait HasMagicProperties
{
    protected string $query;
    protected array $bindings = [];

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setBindings(array $bindings): void
    {
        $this->bindings = $bindings;
    }

    public function __get($property)
    {
        $camelCaseProperty = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
        $getter = 'get' . $camelCaseProperty;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (in_array($property, $this->columns ?? []) && property_exists($this, $property)) {
            return $this->$property;
        }

        if (method_exists($this, $property)) {
            if (property_exists($this, 'relations')) {
                $relation = $this->relations[$property] ?? null;
                if ($relation === null) {
                    $relation = $this->$property();
                    $this->relations[$property] = $relation;
                }
                return $relation;
            }
            return $this->$property();
        }

        throw new \Exception("Property '$property' does not exist on " . static::class);
    }

    public function __set($property, $value)
    {
        $camelCaseProperty = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
        $setter = 'set' . $camelCaseProperty;

        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }

        if (in_array($property, $this->columns ?? [])) {
            $this->$property = $value;
            return;
        }

        if (property_exists($this, 'relations')) {
            $this->relations[$property] = $value;
            return;
        }

        throw new \Exception("Property '$property' is not writable on " . static::class);
    }
}
