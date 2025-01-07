<?php

namespace Ivy;

trait Stash
{
    private static array $stash = [];
    private static ?array $currentData = null;

    /**
     * Initialize stashing for a specific type and fetch all records.
     *
     * @param string $type The class name of the objects to stash.
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function stash(string $type): static
    {
        if (!class_exists($type)) {
            throw new \InvalidArgumentException("Class '$type' does not exist.");
        }

        $model = new $type();
        if (!method_exists($model, 'fetchAll')) {
            throw new \InvalidArgumentException("Class '$type' does not have a fetchAll method.");
        }

        self::$currentData = $model->fetchAll();
        return new static();
    }

    /**
     * Stash objects by a specific column key.
     *
     * @param string $column The column name to use as the key.
     * @return void
     * @throws \InvalidArgumentException
     */
    public function keyByColumn(string $column): void
    {
        if (self::$currentData === null) {
            throw new \RuntimeException("No data available to stash. Call stash() first.");
        }

        foreach (self::$currentData as $instance) {
            if (!property_exists($instance, $column)) {
                throw new \InvalidArgumentException("Column '$column' is not valid for this model.");
            }

            $key = strtolower(str_replace(' ', '_', $instance->{$column}));
            $type = get_class($instance);
            self::$stash[$type][$key] = $instance;
        }

        self::$currentData = null; // Reset after stashing
    }

    /**
     * Retrieve stashed objects of a specific type.
     *
     * @param string $type The class name of the stashed objects.
     * @return array|null
     */
    public static function getStashFrom(string $type): ?array
    {
        return self::$stash[$type] ?? null;
    }
}
