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
        return new static();
    }

    protected function ensureQuery(): void
    {
        if (!isset($this->table) || empty($this->table)) {
            throw new \RuntimeException('Model must define a $table property.');
        }
        $this->query ??= "SELECT * FROM `{$this->table}`";
        $this->bindings ??= [];
    }

    public function select(string|array $columns): static
    {
        $this->ensureQuery();
        $cols = array_map(fn($c) => $c === '*' ? '*' : "`$this->table`.`$c`", (array)$columns);
        $this->query = 'SELECT ' . implode(', ', $cols) . " FROM `$this->table`";
        return $this;
    }

    protected function addCondition(string $column, mixed $value, string $operator = '=', string $boolean = 'AND'): static
    {
        $this->ensureQuery();

        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : " WHERE ";

        if (is_null($value)) {
            $this->query .= "{$prefix}`$this->table`.`$column` IS NULL";
        } elseif (is_array($value)) {
            if (empty($value)) {
                $this->query .= "{$prefix}1=0"; // ensures empty result
            } else {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $this->query .= "{$prefix}`$this->table`.`$column` IN ($placeholders)";
                $this->bindings = array_merge($this->bindings, $value);
            }
        } else {
            $this->query .= "{$prefix}`$this->table`.`$column` $operator ?";
            $this->bindings[] = $value;
        }

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
        return $this->where($column, $value, '!=');
    }

    public function orWhereNot(string $column, mixed $value): static
    {
        return $this->orWhere($column, $value, '!=');
    }

    public function whereIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, '=', 'AND');
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->addCondition($column, $values, '=', 'OR');
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
            ? implode(', ', array_map(fn($c) => "`$this->table`.`$c` $direction", $columns))
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