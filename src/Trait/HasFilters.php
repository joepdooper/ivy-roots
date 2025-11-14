<?php
namespace Ivy\Trait;

trait HasFilters
{
    public function filter(array $filters): static
    {
        foreach ($filters as $column => $condition) {
            if (!is_array($condition)) {
                $this->where($column, $condition);
                continue;
            }

            foreach ($condition as $operator => $value) {
                $operator = strtolower($operator);

                match ($operator) {
                    '=', '==' => $this->where($column, $value),
                    '!=' , '<>' => $this->whereNot($column, $value),
                    '>', '<', '>=', '<=' => $this->where($column, $value, $operator),
                    'like' => $this->where($column, $value, 'LIKE'),
                    'in' => $this->whereIn($column, $value),
                    default => throw new \InvalidArgumentException("Unsupported filter operator: $operator"),
                };
            }
        }

        return $this;
    }
}