<?php
namespace Ivy\Trait;

trait HasQueryBuilder
{
    protected int $bindingCounter = 0;

    public static function query(): static
    {
        /** @phpstan-ignore-next-line */
        return new static();
    }

    public function initQueryBuilder(string $table): void
    {
        $this->table = $table;
        $this->query = "SELECT * FROM `$table`";
        $this->bindings = [];
        $this->bindingCounter = 0;
    }

    public function select(string|array $columns): static
    {
        $cols = array_map(fn($c) => $c === '*' ? '*' : "`$this->table`.`$c`", (array)$columns);
        $this->query = 'SELECT ' . implode(', ', $cols) . " FROM `$this->table`";
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '=', string $boolean = 'AND'): static
    {
        $col = $this->qualifyColumn($column);
        $cond = is_null($value) ? "{$col['qualified']} IS NULL" : "{$col['qualified']} $operator :{$col['binding']}";
        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : " WHERE ";
        $this->query .= $prefix . $cond;
        if (!is_null($value)) $this->bindings[$col['binding']] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): static
    {
        return $this->where($column, $value, $operator, 'OR');
    }

    public function whereNot(string $column, mixed $value, string $boolean = 'AND'): static
    {
        $col = $this->qualifyColumn($column);
        $cond = "{$col['qualified']} != :{$col['binding']}";
        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : " WHERE ";
        $this->query .= $prefix . $cond;
        $this->bindings[$col['binding']] = $value;
        return $this;
    }

    public function orWhereNot(string $column, mixed $value): static
    {
        return $this->whereNot($column, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            $this->query .= str_contains($this->query, 'WHERE') ? " $boolean 1=0" : " WHERE 1=0";
            return $this;
        }

        $col = $this->qualifyColumn($column);
        $placeholders = [];
        foreach ($values as $v) {
            $key = "{$col['binding']}_{$this->bindingCounter}";
            $this->bindingCounter++;
            $placeholders[] = ":$key";
            $this->bindings[$key] = $v;
        }

        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : " WHERE ";
        $this->query .= $prefix . "{$col['qualified']} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function addJoin(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): static
    {
        $this->query .= " $type JOIN `$table` ON `$this->table`.`$firstColumn` $operator `$table`.`$secondColumn`";
        return $this;
    }

    public function sortBy(array|string $columns, string $direction = 'asc'): static
    {
        $order = is_array($columns)
            ? implode(', ', array_map(fn($c) => "`$this->table`.`$c` $direction", $columns))
            : "`$this->table`.`$columns` $direction";
        $this->query .= " ORDER BY $order";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): static
    {
        $this->query .= " LIMIT $limit OFFSET $offset";
        return $this;
    }

    private function qualifyColumn(string $column): array
    {
        $suffix = $this->bindingCounter++;
        if (strpos($column, '.') !== false) {
            [$table, $col] = explode('.', $column, 2);
            return ['qualified' => "`$table`.`$col`", 'binding' => "{$table}_{$col}_$suffix"];
        }
        return ['qualified' => "`$this->table`.`$column`", 'binding' => "{$column}_$suffix"];
    }
}