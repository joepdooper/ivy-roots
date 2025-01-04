<?php

namespace Ivy;

trait Stash
{
    private static array $stash = [];

    public static function stashByColumnKey(string $column): void
    {
        $model = new static();

        if (!in_array($column, $model->columns)) {
            throw new \InvalidArgumentException("Column '$column' is not a valid column for this model.");
        }

        $models = $model->fetchAll();

        foreach ($models as $instance) {
            $key = strtolower(str_replace(' ', '_', $instance->{$column}));
            self::$stash[$key] = $instance;
        }
    }

    public static function getFromStashByKey(string $key): ?static
    {
        return self::$stash[$key] ?? null;
    }
}