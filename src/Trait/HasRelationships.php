<?php
namespace Ivy\Trait;

trait HasRelationships
{
    protected array $relations = [];
    protected array $with = [];

    public function hasOne(string $relatedModelClass, string $foreignKey, string $localKey = 'id'): ?object
    {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $instance = new $relatedModelClass();
        if (method_exists($instance, 'resetQuery')) {
            $instance->resetQuery();
        }

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            $this->relations[$relationName] = null;
            return null;
        }

        return $this->relations[$relationName] = $instance->where($foreignKey, $localValue)->fetchOne();
    }

    public function hasMany(string $relatedModelClass, string $foreignKey, string $localKey = 'id'): array
    {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $instance = new $relatedModelClass();
        if (method_exists($instance, 'resetQuery')) {
            $instance->resetQuery();
        }

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            $this->relations[$relationName] = [];
            return [];
        }

        return $this->relations[$relationName] = $instance->where($foreignKey, $localValue)->fetchAll();
    }

    public function belongsToMany(
        string $relatedClass,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $localKey = 'id',
        string $relatedKey = 'id'
    ): array {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            $this->relations[$relationName] = [];
            return [];
        }

        $db = \Ivy\Manager\DatabaseManager::connection();

        $pivotQuery = "SELECT `$relatedPivotKey` FROM `$pivotTable` WHERE `$foreignPivotKey` = ?";
        $bindings = [$localValue];

        if (isset($this->table)) {
            $pivotQuery .= " AND `entity_table` = ?";
            $bindings[] = $this->table;
        }

        $rows = $db->select($pivotQuery, $bindings);
        if (!$rows) {
            $this->relations[$relationName] = [];
            return [];
        }

        $relatedIds = array_map(static fn($row) => $row[$relatedPivotKey], $rows);

        $related = new $relatedClass();
        $related->resetQuery();

        $models = $related->whereIn($relatedKey, $relatedIds)->fetchAll();

        return $this->relations[$relationName] = $models;
    }

    public function setRelation(string $relationName, $value): void
    {
        $this->relations[$relationName] = $value;
    }

    public function getRelation(string $relationName)
    {
        return $this->relations[$relationName] ?? null;
    }

    public function with(array $relations): static
    {
        $this->with = $relations;
        return $this;
    }
}
