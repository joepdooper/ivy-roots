<?php

namespace Ivy\Shared\Base;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Domain\Collection\EntityCollection;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static whereIn(string $column, array $values)
 * @method static static select(string ...$columns)
 * @method static static find(int $id)
 * @method static static first()
 * @method static static pluck(string $column, ?string $key = null)
 * @method static static value(string $column)
 * @method static static create(array<string, mixed> $attributes)
 * @method static static handle(Entity $entity, bool $bool)
 * @method static static get()
 * @method static static all()
 */
abstract class Entity extends Model
{
    public function newCollection(array $models = []): EntityCollection
    {
        $collection = new EntityCollection($models);

        if (
            method_exists(static::class, 'pagination') &&
            static::pagination()
        ) {
            $collection->setPagination(static::pagination());
        }

        return $collection;
    }
}
