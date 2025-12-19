<?php
namespace Ivy\Trait;

use Ivy\Manager\DatabaseManager;

trait HasRelationships
{
    protected array $relations = [];
    protected array $with = [];

    protected function resolveRelation(string $relatedClass, string $foreignKey, string $localKey = 'id', bool $single = false): mixed
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $relationName = $trace[2]['function'] ?? null; // caller of hasOne/hasMany

        if (!$relationName) {
            throw new \RuntimeException('Cannot determine relation name.');
        }

        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            return $this->relations[$relationName] = $single ? null : [];
        }

        $instance = new $relatedClass();
        $instance->resetQuery();

        $result = $instance->where($foreignKey, $localValue)
            ->{$single ? 'fetchOne' : 'fetchAll'}();

        return $this->relations[$relationName] = $result;
    }

    public function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): ?object
    {
        return $this->resolveRelation($relatedClass, $foreignKey, $localKey, true);
    }

    public function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array
    {
        return $this->resolveRelation($relatedClass, $foreignKey, $localKey, false);
    }

    protected function getPivotRelatedIds(
        string $pivotTable,
        string $relatedPivotKey,
        ?string $foreignPivotKey = null,
        ?string $morphType = null,
        ?string $morphId = null,
        string $localKey = 'id'
    ): array {
        $localValue = $this->{$localKey} ?? null;
        if (!$localValue) return [];

        $db = DatabaseManager::connection();
        $query = "SELECT `$relatedPivotKey` FROM `$pivotTable` WHERE 1=1";
        $params = [];

        if ($foreignPivotKey && !$morphType && !$morphId) {
            $query .= " AND `$foreignPivotKey` = ?";
            $params[] = $localValue;
        } elseif ($morphType && $morphId) {
            $query .= " AND `$morphType` = ? AND `$morphId` = ?";
            $params[] = $this->table;
            $params[] = $localValue;
        }

        $rows = $db->select($query, $params);
        return $rows ? array_column($rows, $relatedPivotKey) : [];
    }

    public function belongsToMany(
        string $relatedClass,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $localKey = 'id',
        string $relatedKey = 'id',
        ?string $morphType = null,
        ?string $morphId = null
    ): array {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        if (isset($this->relations[$relationName])) {
            return $this->relations[$relationName];
        }

        $relatedIds = $this->getPivotRelatedIds($pivotTable, $relatedPivotKey, $foreignPivotKey, $morphType, $morphId, $localKey);
        if (!$relatedIds) return $this->relations[$relationName] = [];

        $related = new $relatedClass();
        $related->resetQuery();

        return $this->relations[$relationName] = $related
            ->whereIn($relatedKey, $relatedIds)
            ->fetchAll();
    }

    protected function insertPivotRow(string $pivotTable, array $data): void
    {
        DatabaseManager::connection()->insert($pivotTable, $data);
    }

    protected function deletePivotRow(string $pivotTable, array $conditions): void
    {
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $where[] = "`$col` = ?";
            $params[] = $val;
        }
        DatabaseManager::connection()->exec(
            "DELETE FROM `$pivotTable` WHERE " . implode(' AND ', $where),
            $params
        );
    }

    public function attachPivot(
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        int $relatedId,
        string $localKey = 'id'
    ): void {
        $localValue = $this->{$localKey} ?? null;
        if (!$localValue) return;
        $this->insertPivotRow($pivotTable, [$foreignPivotKey => $localValue, $relatedPivotKey => $relatedId]);
    }

    public function detachPivot(
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        int $relatedId,
        string $localKey = 'id'
    ): void {
        $localValue = $this->{$localKey} ?? null;
        if (!$localValue) return;
        $this->deletePivotRow($pivotTable, [$foreignPivotKey => $localValue, $relatedPivotKey => $relatedId]);
    }

    public function syncPivot(
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        array $relatedIds,
        string $localKey = 'id',
        ?string $morphType = null,
        ?string $morphId = null
    ): void {
        $relatedIds = array_unique(array_map('intval', $relatedIds));
        $localValue = $this->{$localKey} ?? null;
        if (!$localValue) return;

        $existing = $this->getPivotRelatedIds($pivotTable, $relatedPivotKey, $foreignPivotKey, $morphType, $morphId, $localKey);

        $toAttach = array_diff($relatedIds, $existing);
        $toDetach = array_diff($existing, $relatedIds);

        foreach ($toAttach as $id) {
            $row = $morphType && $morphId
                ? [$morphType => $this->table, $morphId => $localValue, $relatedPivotKey => $id]
                : [$foreignPivotKey => $localValue, $relatedPivotKey => $id];
            $this->insertPivotRow($pivotTable, $row);
        }

        foreach ($toDetach as $id) {
            $conditions = $morphType && $morphId
                ? [$morphType => $this->table, $morphId => $localValue, $relatedPivotKey => $id]
                : [$foreignPivotKey => $localValue, $relatedPivotKey => $id];
            $this->deletePivotRow($pivotTable, $conditions);
        }
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

    public function wherePivotHasAll(
        string $pivotTable,
        string $valueColumn,
        array $values,
        string $entityColumn = 'entity_table'
    ): static {
        if (empty($values)) {
            return $this;
        }

        $table = $this->table;
        $count = count($values);
        $placeholders = implode(',', array_fill(0, $count, '?'));

        $sql = "
        SELECT entity_id
        FROM {$pivotTable}
        WHERE {$entityColumn} = ?
          AND {$valueColumn} IN ($placeholders)
        GROUP BY entity_id
        HAVING COUNT(DISTINCT {$valueColumn}) = ?
    ";

        $params = array_merge([$table], $values, [$count]);
        $rows = DatabaseManager::connection()->select($sql, $params);
        $entityIds = !empty($rows) ? array_column($rows, 'entity_id') : [];

        if (empty($entityIds)) {
            return $this->where("{$table}.id", -1);
        }

        return $this->whereIn("{$table}.id", $entityIds);
    }
}