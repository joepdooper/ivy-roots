<?php

namespace Ivy;

use Delight\Db\Throwable\EmptyWhereClauseError;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use HTMLPurifier_Config;
use HTMLPurifier;

abstract class Model
{
    protected string $table;
    protected string $path;
    protected array $columns = [];
    protected string $query = '';
    protected array $bindings = [];
    protected ?HTMLPurifier $purifier = null;
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

        throw new \Exception("Property '$property' does not exist.");
    }

    public function __set($property, $value)
    {
        $setter = 'set' . ucfirst($property);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            throw new \Exception("Property '$property' is not writable.");
        }
    }

    public function getPath(): string
    {
        return $this->path;
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

        return array_map(fn($row) => static::createInstance()->populate($row), $rows ?? []);
    }

    public function fetchOne(): ?static
    {
        $data = !empty($this->bindings)
            ? DB::getConnection()->selectRow($this->query, $this->bindings)
            : DB::getConnection()->selectRow($this->query);

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

        $set = $this->sanitize($set);
        try {
            DB::getConnection()->insert($this->table, $set);
        } catch (IntegrityConstraintViolationException $e) {
            Message::add($e->getMessage());
        }

        return DB::getConnection()->getLastInsertId();
    }

    public function update(): bool|int
    {
        $set = $this->toAssocArray();

        if(empty($set)){
            return false;
        }

        $set = $this->sanitize($set);

        try {
            DB::getConnection()->update($this->table, $set, $this->bindings);
        } catch (IntegrityConstraintViolationException $e) {
            Message::add($e->getMessage());
        }

        return DB::getConnection()->getLastInsertId();
    }

    public function delete(): bool|int|string
    {
        try {
            DB::getConnection()->delete($this->table, $this->bindings);
        } catch (EmptyWhereClauseError $e) {
            Message::add($e->getMessage());
        }

        return DB::getConnection()->getLastInsertId();
    }

    public function save(array $data): bool|int
    {
        if (!empty($data['id'])) {
            if (isset($data['delete'])) {
                return $this->where('id', $data['id'])->delete();
            } else {
                return $this->populate($data)->where('id', $data['id'])->update();
            }
        } else {
            if (!empty($data['name'])) {
                return $this->populate($data)->insert();
            }
        }
        return false;
    }

    protected function getPurifier(): HTMLPurifier
    {
        if ($this->purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $this->purifier = new HTMLPurifier($config);
        }

        return $this->purifier;
    }

    public function sanitize(array $array): array
    {
        $purifier = $this->getPurifier();

        return array_map(fn($value) => $purifier->purify($value), $array);
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
}
