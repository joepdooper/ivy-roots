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

        $rows = $db->select($query, $bindings) ?: [];

        $this->resetQuery();
        return $rows;
    }

    public function fetchAll(): array
    {
        $rows = $this->fetchAllRaw();

        /** @phpstan-ignore-next-line */
        $models = array_map(fn($row) => (new static())->populate($row), $rows);

        if (!empty($this->with)) {
            foreach ($models as $model) {
                foreach ($this->with as $relation) {
                    if (method_exists($model, $relation)) {
                        $related = $model->$relation();
                        $model->setRelation($relation, $related);
                    }
                }
            }
        }

        return $models;
    }

    public function fetchOne(): ?static
    {
        $result = $this->limit(1)->fetchAll();
        $this->resetQuery();
        return $result[0] ?? null;
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
        /** @phpstan-ignore-next-line */
        $instance = new static();

        if ($data !== null) {
            $instance->populate($data);
        }
        return $instance;
    }
}
