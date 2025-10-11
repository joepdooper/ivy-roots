<?php
namespace Ivy\Trait;

trait Filter
{
    public function filter(array $filters): static
    {
        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                foreach ($value as $operator => $operand) {
                    $this->applyOperator($column, $operator, $operand);
                }
            } else {
                $this->where($column, $value);
            }
        }

        return $this;
    }

    protected function applyOperator(string $column, string $operator, mixed $value): void
    {
        switch (strtolower($operator)) {
            case 'like':
                $this->where($column, $value, 'LIKE');
                break;

            case 'not':
                $this->whereNot($column, $value);
                break;

            case 'in':
                $placeholders = [];
                foreach ($value as $i => $val) {
                    $binding = "{$column}_in_$i";
                    $placeholders[] = ":$binding";
                    $this->bindings[$binding] = $val;
                }
                $this->query .= (str_contains($this->query, 'WHERE') ? " AND " : " WHERE ")
                    . "`$this->table`.`$column` IN (" . implode(',', $placeholders) . ")";
                break;

            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $binding1 = "{$column}_between_1";
                    $binding2 = "{$column}_between_2";
                    $this->bindings[$binding1] = $value[0];
                    $this->bindings[$binding2] = $value[1];
                    $this->query .= (str_contains($this->query, 'WHERE') ? " AND " : " WHERE ")
                        . "`$this->table`.`$column` BETWEEN :$binding1 AND :$binding2";
                }
                break;
        }
    }
}
