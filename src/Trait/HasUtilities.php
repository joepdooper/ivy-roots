<?php
namespace Ivy\Trait;

trait HasUtilities
{
    public function toAssocArray(): array
    {
        $assocArray = [];
        $objectVars = get_object_vars($this);
        foreach ($this->columns as $column) {
            if (property_exists($this, $column) && array_key_exists($column, $objectVars)) {
                $assocArray[$column] = $this->$column;
            }
        }
        return $assocArray;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function hasId(): bool
    {
        return isset($this->id) && $this->id > 0;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function count(): int
    {
        $countQuery = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) FROM', $this->query);
        return (int)\Ivy\Manager\DatabaseManager::connection()->selectValue($countQuery, $this->bindings);
    }

    protected function resetQuery(): void
    {
        $this->query = "SELECT * FROM `{$this->table}`";
        $this->bindings = [];
    }
}
