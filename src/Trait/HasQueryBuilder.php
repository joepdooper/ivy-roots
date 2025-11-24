<?php
namespace Ivy\Trait;

trait HasQueryBuilder
{
    public static function query(): static
    {
        return new static();
    }

    public function select(string|array $columns): static
    {
        $cols = [];
        foreach ((array)$columns as $column) {
            $cols[] = $column === '*' ? '*' : $this->qualifyColumn($column)['qualified'];
        }
        $this->query = 'SELECT ' . implode(', ', $cols) . ' FROM `' . $this->table . '`';
        return $this;
    }

    public function where(string $column, $value = null, string $operator = '=', string $boolean = 'AND'): static
    {
        $col = $this->qualifyColumn($column);

        $condition = is_null($value)
            ? "{$col['qualified']} IS NULL"
            : "{$col['qualified']} $operator :{$col['binding']}";

        $prefix = str_contains($this->query, 'WHERE') ? " $boolean " : " WHERE ";
        $this->query .= $prefix . $condition;

        if (!is_null($value)) {
            $this->bindings[$col['binding']] = $value;
        }

        return $this;
    }

    public function orWhere(string $column, $value = null, string $operator = '='): static
    {
        return $this->where($column, $value, $operator, 'OR');
    }

    public function whereNot(string $column, $value, string $boolean = 'AND'): static
    {
        $col = $this->qualifyColumn($column);

        $this->query .= str_contains($this->query, 'WHERE')
            ? " $boolean {$col['qualified']} != :{$col['binding']}"
            : " WHERE {$col['qualified']} != :{$col['binding']}";

        $this->bindings[$col['binding']] = $value;

        return $this;
    }

    public function orWhereNot(string $column, $value): static
    {
        return $this->whereNot($column, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            $this->query .= str_contains($this->query, 'WHERE') ? " $boolean 1 = 0" : " WHERE 1 = 0";
            return $this;
        }

        $col = $this->qualifyColumn($column);
        $placeholders = [];
        foreach ($values as $i => $value) {
            $key = "{$col['binding']}_{$i}";
            $placeholders[] = ":$key";
            $this->bindings[$key] = $value;
        }

        $this->query .= str_contains($this->query, 'WHERE')
            ? " $boolean {$col['qualified']} IN (" . implode(', ', $placeholders) . ")"
            : " WHERE {$col['qualified']} IN (" . implode(', ', $placeholders) . ")";

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

    public function sortBy($columns, string $direction = 'asc'): static
    {
        $orderByString = is_array($columns)
            ? implode(', ', array_map(fn($col) => "`$this->table`.`$col` $direction", $columns))
            : "`$this->table`.`$columns` $direction";
        $this->query .= " ORDER BY $orderByString";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): static
    {
        $this->query .= " LIMIT $limit OFFSET $offset";
        return $this;
    }

    public function initQueryBuilder(string $table): void
    {
        $this->query = "SELECT * FROM `$table`";
        $this->bindings = [];
    }

    private function qualifyColumn(string $column): array
    {
        if (strpos($column, '.') !== false) {
            [$table, $col] = explode('.', $column, 2);
            return ['qualified' => "`$table`.`$col`", 'binding' => "{$table}_{$col}"];
        }
        return ['qualified' => "`$this->table`.`$column`", 'binding' => $column];
    }
}