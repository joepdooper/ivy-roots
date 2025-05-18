<?php

namespace Ivy\Abstract;

use Delight\Db\Throwable\EmptyWhereClauseError;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use Ivy\Manager\DatabaseManager;
use Ivy\Path;

abstract class Model
{
    protected string $table;
    protected string $path;
    protected array $columns = [];
    protected string $query = '';
    protected array $bindings = [];
    protected int $id;
    protected array $relationCache = [];

    public function __construct()
    {
        $this->query = "SELECT * FROM `$this->table`";
    }

    public function __get($property)
    {
        $camelCaseProperty = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
        $getter = 'get' . $camelCaseProperty;

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
        $camelCaseProperty = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
        $setter = 'set' . $camelCaseProperty;

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
        $formattedColumns = [];

        foreach ($columns as $column) {
            if (strpos($column, '.') !== false) {
                [$table, $col] = explode('.', $column, 2);
                $formattedColumns[] = "`$table`.`$col`";
            } else {
                $formattedColumns[] = "`$this->table`.`$column`";
            }
        }

        $columnString = implode(', ', $formattedColumns);
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

    public function whereRaw(string $condition, array $bindings = []): static
    {
        $this->query .= str_contains($this->query, 'WHERE')
            ? " AND $condition"
            : " WHERE $condition";

        $this->bindings = array_merge($this->bindings, $bindings);

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
            ? DatabaseManager::connection()->select($this->query, $this->bindings)
            : DatabaseManager::connection()->select($this->query);
        $this->resetQuery();

        return array_map(fn($row) => static::createInstance()->populate($row), $rows ?? []);
    }

    public function fetchOne(): ?static
    {
        $data = !empty($this->bindings)
            ? DatabaseManager::connection()->selectRow($this->query, $this->bindings)
            : DatabaseManager::connection()->selectRow($this->query);
        $this->resetQuery();

        return $data ? $this->createInstance()->populate($data) : null;
    }

    public function hasMany(string $relatedModelClass, string $foreignKey, string $localKey = 'id'): array
    {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        if (isset($this->relationCache[$relationName])) {
            return $this->relationCache[$relationName];
        }

        /** @var Model $instance */
        $instance = new $relatedModelClass();

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            return [];
        }

        $related = $instance->where($foreignKey, $localValue)->fetchAll();

        return $this->relationCache[$relationName] = $related;
    }

    public function hasOne(string $relatedModelClass, string $foreignKey, string $localKey = 'id'): ?Model
    {
        $relationName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        if (isset($this->relationCache[$relationName])) {
            return $this->relationCache[$relationName];
        }

        /** @var Model $instance */
        $instance = new $relatedModelClass();

        $localValue = $this->{$localKey} ?? null;
        if ($localValue === null) {
            return null;
        }

        $related = $instance->where($foreignKey, $localValue)->fetchOne();

        return $this->relationCache[$relationName] = $related;
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

        DatabaseManager::connection()->insert($this->table, $set);

        $this->resetQuery();

        return DatabaseManager::connection()->getLastInsertId();
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

        DatabaseManager::connection()->update($this->table, $set, $this->bindings);

        $this->resetQuery();

        return DatabaseManager::connection()->getLastInsertId();
    }

    public function delete(): bool|int|string
    {
        if (empty($this->bindings) && isset($this->id)) {
            $this->bindings['id'] = $this->id;
        }

        DatabaseManager::connection()->delete($this->table, $this->bindings);

        $this->resetQuery();

        return DatabaseManager::connection()->getLastInsertId();
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
            $camelCaseProperty = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            $setter = 'set' . $camelCaseProperty;
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (in_array($key, $this->columns)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    protected static function createInstance(): static
    {
        return new static();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function count(): int {
        $countQuery = preg_replace('/SELECT.*?FROM/', 'SELECT COUNT(*) FROM', $this->query);
        return (int) DatabaseManager::connection()->selectValue($countQuery, $this->bindings);
    }

    public function limit(int $limit, int $offset = 0): static {
        $this->query .= " LIMIT $limit OFFSET $offset";
        return $this;
    }

    public function policy(string $action): ?bool
    {
        $modelClass = get_class($this);
        $modelName = (new \ReflectionClass($modelClass))->getShortName();
        $namespace = (new \ReflectionClass($modelClass))->getNamespaceName();

        $policyClass = "{$namespace}\\{$modelName}Policy";

        if (!class_exists($policyClass)) {
            return null;
        }

        if (!method_exists($policyClass, $action)) {
            return null;
        }

        return $policyClass::$action($this);
    }
}
