<?php

namespace Ivy\Trait;

use Ivy\Manager\DatabaseManager;

trait CanPersist
{
    public function fetchAllRaw(): array
    {
        $db = DatabaseManager::connection();
        $query = $this->query ?? "SELECT * FROM `{$this->table}`";
        $bindings = $this->bindings ?? [];

        return $db->select($query, $bindings) ?: [];
    }

    public function fetchAll(): array
    {
        $rows = $this->fetchAllRaw();
        $instances = [];

        foreach ($rows as $row) {
            $instance = static::hydrate($row);

            foreach ($this->with ?? [] as $relation) {
                if (method_exists($instance, $relation)) {
                    $instance->setRelation($relation, $instance->$relation());
                }
            }

            $instances[] = $instance;
        }

        return $instances;
    }


    public function fetchOne(): ?static
    {
        $this->query .= " LIMIT 1";
        $rows = $this->fetchAllRaw();
        return isset($rows[0]) ? $this->hydrate($rows[0]) : null;
    }

    public function populate(array $data): static
    {
        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->columns ?? [])) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function hydrate(array $data = null): static
    {
        $instance = new static();
        if ($data !== null) {
            $instance->populate($data);
        }
        return $instance;
    }
}
