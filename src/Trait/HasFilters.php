<?php
namespace Ivy\Trait;

trait HasFilters
{
    public function filter(array $filters): static
    {
        foreach ($filters as $key => $value) {
            if (strpos($key, '.') !== false) {
                [$relationName, $column] = explode('.', $key, 2);
                if (method_exists($this, $relationName)) {
                    $this->applyRelationFilter($relationName, $column, $value);
                    continue;
                }
            }

            if (is_array($value)) {
                foreach ($value as $operator => $operand) {
                    $this->applyOperator($key, $operator, $operand);
                }
            } else {
                $this->where($key, $value);
            }
        }

        return $this;
    }

    protected function applyRelationFilter(string $relationName, string $column, mixed $value): void
    {
        $relationData = $this->$relationName();

        if (!isset($relationData['table'])) {
            throw new \RuntimeException("Relation '$relationName' must return table info.");
        }

        $relatedTable = $relationData['table'];
        $pivotTable   = $relationData['pivot'] ?? null;
        $foreignPivot = $relationData['foreignPivotKey'] ?? null;
        $relatedPivot = $relationData['relatedPivotKey'] ?? null;
        $localKey     = $relationData['localKey'] ?? 'id';
        $localValue   = $this->{$localKey} ?? null;

        if ($pivotTable) {
            if ($foreignPivot) {
                $this->addJoin($pivotTable, $foreignPivot, '=', $localValue);
            } else if (isset($relationData['morphType'], $relationData['morphId'])) {
                $this->addJoin(
                    $pivotTable,
                    $relationData['morphId'],
                    '=',
                    $localValue
                );
            }
            $this->addJoin($relatedTable, $relatedPivot, '=', "{$relatedTable}.id");
        } else {
            $this->addJoin($relatedTable, 'id', '=', $localValue);
        }

        $bindingKey = "{$relationName}_{$column}";
        $this->query .= str_contains($this->query, 'WHERE')
            ? " AND `$relatedTable`.`$column` = :$bindingKey"
            : " WHERE `$relatedTable`.`$column` = :$bindingKey";
        $this->bindings[$bindingKey] = $value;
    }

    protected function applyOperator(string $column, string $operator, mixed $value): void
    {
        switch (strtolower($operator)) {
            case 'like':
                $this->where($column, "%{$value}%", 'LIKE');
                break;
            case 'not':
                $this->whereNot($column, $value);
                break;
            case 'in':
                $this->whereIn($column, (array)$value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $binding1 = "{$column}_1";
                    $binding2 = "{$column}_2";
                    $this->bindings[$binding1] = $value[0];
                    $this->bindings[$binding2] = $value[1];
                    $this->query .= (str_contains($this->query, 'WHERE') ? " AND " : " WHERE ")
                        . "`$this->table`.`$column` BETWEEN :$binding1 AND :$binding2";
                }
                break;
            default:
                $this->where($column, $value, strtoupper($operator));
        }
    }
}