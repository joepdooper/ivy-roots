<?php

namespace Ivy\Shared\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Ivy\Shared\Domain\Collection\EntityCollection;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static whereIn(string $column, array<int, mixed> $values)
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
    /**
     * @param mixed $query
     * @return EntityBuilder<Entity>
     */
    public function newEloquentBuilder($query): EntityBuilder
    {
        return new EntityBuilder($query);
    }

    /**
     * @param array<int|string, static> $models
     * @return EntityCollection|Collection<int|string, static>
     */
    public function newCollection(array $models = []): EntityCollection|Collection
    {
        $collection = new EntityCollection($models);

        if (
            method_exists(static::class, 'paginationState') &&
            static::paginationState()
        ) {
            $collection->setPaginationState(static::paginationState());
        }

        if (
            method_exists(static::class, 'searchState') &&
            static::searchState()
        ) {
            $collection->setSearchState(static::searchState());
        }

        return $collection;
    }
}
