<?php

namespace Ivy\Abstract;

use Ivy\Trait\HasQueryBuilder;
use Ivy\Trait\CanPersist;
use Ivy\Trait\HasRelationships;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\HasUtilities;
use Ivy\Trait\HasMagicProperties;

abstract class Model
{
    use HasQueryBuilder, CanPersist, HasRelationships, HasPolicies, HasUtilities, HasMagicProperties;

    protected string $table;
    protected string $path;
    protected array $columns = [];
    protected ?int $id = null;
    protected array $relationCache = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getColumns(): array
    {
        return $this->columns ?? [];
    }

    public function insert(): static
    {
        $db = \Ivy\Manager\DatabaseManager::connection();
        $data = [];
        foreach ($this->columns as $column) {
            if (property_exists($this, $column)) {
                $data[$column] = $this->$column;
            }
        }

        $db->insert($this->table, $data);
        $this->id = (int) $db->getLastInsertId();
        $this->resetQuery();

        return $this;
    }

    public function update(): static
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot update model without ID.");
        }

        $db = \Ivy\Manager\DatabaseManager::connection();

        $data = [];
        foreach ($this->columns as $column) {
            if (property_exists($this, $column)) {
                $data[$column] = $this->$column;
            }
        }

        $db->update($this->table, $data, ['id' => $this->id]);
        $this->resetQuery();

        return $this;
    }

    public function delete(): bool
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot delete model without ID.");
        }

        $db = \Ivy\Manager\DatabaseManager::connection();

        $deleted = $db->delete($this->table, ['id' => $this->id]);
        $this->resetQuery();

        $this->id = null;
        return (bool)$deleted;
    }

    public function deleteAll(): int
    {
        $db = \Ivy\Manager\DatabaseManager::connection();
        $query = preg_replace('/SELECT \* FROM/', "DELETE FROM", $this->query, 1);
        return $db->exec($query, $this->bindings ?? []);
    }

    public function save(): static
    {
        if ($this->id === null) {
            return $this->insert();
        }

        return $this->update();
    }
}
