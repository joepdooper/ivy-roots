<?php

namespace Ivy;

trait Stash
{
    private static array $stash = [];

    /**
     * Stash objects by column key into the given type (e.g., Setting).
     *
     * @param string $type The class name of the object to stash.
     * @param string $column The column name to use as the key.
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function stashByColumnKey(string $type, string $column): void
    {
        if (!class_exists($type)) {
            throw new \InvalidArgumentException("Class '$type' does not exist.");
        }

        $model = new $type();

        if (!property_exists($model, 'columns') || !in_array($column, $model->getColumns())) {
            throw new \InvalidArgumentException("Column '$column' is not valid for this model.");
        }

        $models = $model->fetchAll();

        foreach ($models as $instance) {
            $key = strtolower(str_replace(' ', '_', $instance->{$column}));
            self::$stash[$type][$key] = $instance;
        }
    }

    /**
     * Retrieve stashed objects of a specific type.
     *
     * @param string $type The class name of the stashed objects.
     * @return static|null
     */
    public static function getStashFrom(string $type): ?array
    {
        return self::$stash[$type] ?? null;
    }
}