<?php
namespace Ivy\Utility;

class QueryBuilder
{
    protected string $table;
    protected array $columns = [];
    protected array $bindings = [];
    protected string $query = '';

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->reset();
    }

    public function reset(): static
    {
        $this->query = "SELECT * FROM `$this->table`";
        $this->bindings = [];
        return $this;
    }

    public function select(array|string $columns): static
    {
        $cols = [];
        foreach ((array)$columns as $col) {
            $cols[] = $col === '*' ? '*' : $this->qualifyColumn($col)['qualified'];
        }
        $this->query = 'SELECT ' . implode(', ', $cols) . " FROM `$this->table`";
        return $this;
    }

    public function where(string $column, mixed $value = null, string $operator = '='): static
    {
        $col = $this->qualifyColumn($column);

        if (is_null($value)) {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND {$col['qualified']} IS NULL"
                : " WHERE {$col['qualified']} IS NULL";
        } else {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND {$col['qualified']} $operator :{$col['binding']}"
                : " WHERE {$col['qualified']} $operator :{$col['binding']}";
            $this->bindings[$col['binding']] = $value;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->query .= str_contains($this->query, 'WHERE') ? " AND 1=0" : " WHERE 1=0";
            return $this;
        }

        $col = $this->qualifyColumn($column);
        $placeholders = [];

        foreach ($values as $i => $value) {
            $key = "{$col['binding']}_{$i}";
            $placeholders[] = ":$key";
            $this->bindings[$key] = $value;
        }

        $in = implode(', ', $placeholders);
        $this->query .= str_contains($this->query, 'WHERE')
            ? " AND {$col['qualified']} IN ($in)"
            : " WHERE {$col['qualified']} IN ($in)";

        return $this;
    }

    public function addJoin(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): static
    {
        $this->query .= " $type JOIN `$table` ON `$this->table`.`$firstColumn` $operator `$table`.`$secondColumn`";
        return $this;
    }

    public function sortBy(array|string $columns, string $direction = 'ASC'): static
    {
        $orderBy = is_array($columns)
            ? implode(', ', array_map(fn($col) => "`$this->table`.`$col` $direction", $columns))
            : "`$this->table`.`$columns` $direction";

        $this->query .= " ORDER BY $orderBy";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): static
    {
        $this->query .= " LIMIT $limit OFFSET $offset";
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    protected function qualifyColumn(string $column): array
    {
        if (strpos($column, '.') !== false) {
            [$table, $col] = explode('.', $column, 2);
            return ['qualified' => "`$table`.`$col`", 'binding' => "{$table}_{$col}"];
        }
        return ['qualified' => "`$this->table`.`$column`", 'binding' => $column];
    }
}
