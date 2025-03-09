<?php

namespace Ivy;

use Delight\Db\Throwable\EmptyWhereClauseError;
use Delight\Db\Throwable\IntegrityConstraintViolationException;

abstract class Model
{
    protected string $table;
    protected string $path;
    protected array $columns = [];
    protected string $query = '';
    protected array $bindings = [];
    protected int $id;

    public function __construct()
    {
        $this->query = "SELECT * FROM `$this->table`";
    }

    public function __get($property)
    {
        $getter = 'get' . ucfirst($property);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (in_array($property, $this->columns) && property_exists($this, $property)) {
            return $this->$property;
        }

        throw new \Exception("Property '$property' does not exist.");
    }

    public function __set($property, $value)
    {
        $setter = 'set' . ucfirst($property);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }

        if (in_array($property, $this->columns)) {
            $this->$property = $value;
            return;
        }

        throw new \Exception("Property '$property' is not writable.");
    }

    public function getPath(): string
    {
        return Path::get('BASE_PATH') . $this->path;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function hasId(): bool
    {
        return isset($this->id) && $this->id > 0;
    }

    protected function resetQuery(): void {
        $this->query = "SELECT * FROM `$this->table`";
        $this->bindings = [];
    }

    public function select(array $columns): static
    {
        $validColumns = array_intersect($columns, $this->columns);
        if (empty($validColumns)) {
            throw new \Exception("Invalid column selection: None of the specified columns exist in the model.");
        }
        $columnString = implode(', ', array_map(fn($col) => "`$this->table`.`$col`", $validColumns));
        $this->query = "SELECT $columnString FROM `$this->table`";

        return $this;
    }

    public function where(string $column, $value = null, string $operator = '='): static
    {
        if (is_null($value)) {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND `$this->table`.`$column` IS NULL"
                : " WHERE `$this->table`.`$column` IS NULL";
        } else {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND `$this->table`.`$column` $operator :$column"
                : " WHERE `$this->table`.`$column` $operator :$column";

            $this->bindings[$column] = $value;
        }

        return $this;
    }

    public function whereNot(string $column, $value): static
    {
        if (is_null($value)) {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND `$this->table`.`$column` IS NOT NULL"
                : " WHERE `$this->table`.`$column` IS NOT NULL";
        } else {
            $this->query .= str_contains($this->query, 'WHERE')
                ? " AND `$this->table`.`$column` != :$column"
                : " WHERE `$this->table`.`$column` != :$column";

            $this->bindings[$column] = $value;
        }

        return $this;
    }

    public function excludeBy(string $column, $value): static
    {
        $this->query .= str_contains($this->query, 'WHERE')
            ? " AND `$this->table`.`$column` != :$column"
            : " WHERE `$this->table`.`$column` != :$column";
        $this->bindings[$column] = $value;

        return $this;
    }

    public function addJoin(string $table, string $firstColumn, string $operator, string $secondColumn, string $type = 'INNER'): static
    {
        $this->query .= " $type JOIN `$table` ON `$this->table`.`$firstColumn` $operator `$table`.`$secondColumn`";

        return $this;
    }

    public function sortBy($columns, string $direction = 'asc'): static
    {
        $orderByString = is_array($columns)
            ? implode(', ', array_map(fn($column) => "`$this->table`.`$column` $direction", $columns))
            : "`$this->table`.`$columns` $direction";

        $this->query .= " ORDER BY $orderByString";

        return $this;
    }

    public function fetchAll(): array
    {
        $rows = !empty($this->bindings)
            ? DB::getConnection()->select($this->query, $this->bindings)
            : DB::getConnection()->select($this->query);
        $this->resetQuery();

        return array_map(fn($row) => static::createInstance()->populate($row), $rows ?? []);
    }

    public function fetchOne(): ?static
    {
        $data = !empty($this->bindings)
            ? DB::getConnection()->selectRow($this->query, $this->bindings)
            : DB::getConnection()->selectRow($this->query);
        $this->resetQuery();

        return $data ? $this->createInstance()->populate($data) : null;
    }

    public function organizeByColumn(string $columnName): static
    {
        $this->rows = array_reduce($this->fetchAll(), function ($carry, $object) use ($columnName) {
            $key = str_replace(' ', '_', strtolower($object->{$columnName} ?? ''));
            $carry[$key] = $object;
            return $carry;
        }, []);

        return $this;
    }

    public function toAssocArray(): array
    {
        $assocArray = [];

        foreach ($this->columns as $column) {
            if (property_exists($this, $column) && isset($this->{$column})) {
                $assocArray[$column] = $this->{$column};
            }
        }

        return $assocArray;
    }

    public function insert(): bool|int
    {
        $set = $this->toAssocArray();

        if(empty($set)){
            return false;
        }

        if (!empty($this->columns)) {
            $set = array_intersect_key($set, array_flip($this->columns));
        }

        try {
            DB::getConnection()->insert($this->table, $set);
        } catch (IntegrityConstraintViolationException $e) {
            Message::add($e->getMessage());
        }

        $this->resetQuery();

        return DB::getConnection()->getLastInsertId();
    }

    public function update(): bool|int
    {
        $set = $this->toAssocArray();

        if(empty($set)){
            return false;
        }

        if (!empty($this->columns)) {
            $set = array_intersect_key($set, array_flip($this->columns));
        }

        if (empty($this->bindings) && isset($this->id)) {
            $this->bindings['id'] = $this->id;
        }

        try {
            DB::getConnection()->update($this->table, $set, $this->bindings);
        } catch (IntegrityConstraintViolationException $e) {
            Message::add($e->getMessage());
        }

        $this->resetQuery();

        return DB::getConnection()->getLastInsertId();
    }

    public function delete(): bool|int|string
    {
        if (empty($this->bindings) && isset($this->id)) {
            $this->bindings['id'] = $this->id;
        }

        try {
            DB::getConnection()->delete($this->table, $this->bindings);
        } catch (EmptyWhereClauseError $e) {
            Message::add($e->getMessage());
        }

        $this->resetQuery();

        return DB::getConnection()->getLastInsertId();
    }

    public function save(array $data): bool|int
    {
        $id = false;
        if (!empty($data['id'])) {
            if (isset($data['delete'])) {
                $id = $this->where('id', $data['id'])->delete();
            } else {
                $id = $this->populate($data)->where('id', $data['id'])->update();
            }
        } else {
            if (array_filter(array_intersect_key($data, array_flip($this->columns)))) {
                $id = $this->populate($data)->insert();
            }
        }
        $this->resetQuery();
        return $id;
    }

    public function populate(array $data): static
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->columns) && isset($value) || $key === 'id') {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    protected static function createInstance(): static
    {
        return new static();
    }

    public function count(): int {
        $countQuery = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) FROM', $this->query);
        return (int) DB::getConnection()->selectValue($countQuery, $this->bindings);
    }

    public function limit(int $limit, int $offset = 0): static {
        $this->query .= " LIMIT $limit OFFSET $offset";
        return $this;
    }
}
