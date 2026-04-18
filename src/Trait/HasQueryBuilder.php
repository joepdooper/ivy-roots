<?php

namespace Ivy\Trait;

trait HasQueryBuilder
{
    protected string $table;

    protected string $query;

    protected array $bindings = [];

    public static function query(): static
    {
        /** @phpstan-ignore-next-line */
        return new static;
    }

    protected function ensureQuery(): void
    {
        if (! isset($this->table) || empty($this->table)) {
            throw new \RuntimeException('Model must define a $table property.');
        }
        $this->query ??= "SELECT * FROM `{$this->table}`";
        $this->bindings ??= [];
    }

    public function select(string|array $columns): static
    {
        $this->ensureQuery();
        $cols = array_map(fn ($c) => $c === '*' ? '*' : "`$this->table`.`$c`", (array) $columns);
        $this->query = 'SELECT '.implode(', ', $cols)." FROM `$this->table`";

        return $this;
    }

    protected function addCondition(string $column, mixed $value, string $operator = '=', string $boolean = 'AND'): static
    {
        $this->ensureQuery();

        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : ' WHERE ';

        if (is_null($value)) {
            $this->query .= $operator === '!='
                ? "{$prefix}`$this->table`.`$column` IS NOT NULL"
                : "{$prefix}`$this->table`.`$column` IS NULL";

            return $this;
        }

        if (is_array($value)) {
            $operator = strtoupper($operator);

            if ($operator === '=' || $operator === 'IN') {
                $operator = 'IN';
            } elseif ($operator === '!=' || $operator === 'NOT IN') {
                $operator = 'NOT IN';
            } else {
                throw new \InvalidArgumentException(
                    "Array values only support IN or NOT IN operators."
                );
            }

            if (empty($value)) {
                if ($operator === 'IN') {
                    $this->query .= "{$prefix}1=0";
                }
                return $this;
            }

            $placeholders = implode(',', array_fill(0, count($value), '?'));

            $this->query .= "{$prefix}`$this->table`.`$column` $operator ($placeholders)";
            $this->bindings = [...$this->bindings, ...$value];

            return $this;
        }

        $this->query .= "{$prefix}`$this->table`.`$column` $operator ?";
        $this->bindings[] = $value;

        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): static
    {
        return $this->addCondition($column, $value, $operator, 'AND');
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): static
    {
        return $this->addCondition($column, $value, $operator, 'OR');
    }

    public function whereNot(string $column, mixed $value): static
    {
        return $this->addCondition($column, $value, '!=', 'AND');
    }

    public function orWhereNot(string $column, mixed $value): static
    {
        return $this->addCondition($column, $value, '!=', 'OR');
    }

    public function whereIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, 'IN', 'AND');
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, 'IN', 'OR');
    }

    public function whereNotIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, 'NOT IN', 'AND');
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, 'NOT IN', 'OR');
    }

    public function addJoin(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): static
    {
        $this->ensureQuery();
        $this->query .= " $type JOIN `$table` ON `$this->table`.`$firstColumn` $operator `$table`.`$secondColumn`";

        return $this;
    }

    public function sortBy(array|string $columns, string $direction = 'asc'): static
    {
        $this->ensureQuery();
        $order = is_array($columns)
            ? implode(', ', array_map(fn ($c) => "`$this->table`.`$c` $direction", $columns))
            : "`$this->table`.`$columns` $direction";
        $this->query .= " ORDER BY $order";

        return $this;
    }

    public function limit(int $limit, int $offset = 0): static
    {
        $this->ensureQuery();
        $this->query .= " LIMIT $limit OFFSET $offset";

        return $this;
    }

    protected function resetQuery(): void
    {
        $this->ensureQuery();
        $this->query = "SELECT * FROM `{$this->table}`";
        $this->bindings = [];
    }
}
