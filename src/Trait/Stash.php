<?php

namespace Ivy\Trait;

trait Stash
{
    private static array $stashData = [];

    public static function stash(): static
    {
        $type = static::class;

        $instance = new $type();
        $instances = $instance->fetchAll();

        self::$stashData[$type] = $instances;

        return $instance;
    }

    public function keyByColumn(string $column): void
    {
        $type = static::class;

        if (!isset(self::$stashData[$type])) {
            throw new \RuntimeException("No data available to stash. Call stash() first.");
        }

        $stashData = [];
        foreach (self::$stashData[$type] as $instance) {
            $getter = 'get' . str_replace('_', '', ucwords($column, '_'));

            if (method_exists($instance, $getter)) {
                $key = strtolower(str_replace(' ', '_', $instance->$getter()));
            } elseif (property_exists($instance, $column)) {
                $key = strtolower(str_replace(' ', '_', $instance->$column));
            } else {
                throw new \InvalidArgumentException("Column '$column' is not valid for this model.");
            }

            $stashData[$key] = $instance;
        }

        self::$stashData[$type] = $stashData;
    }

    public static function stashGet(string $key): mixed
    {
        return self::$stashData[static::class][$key] ?? null;
    }

    public static function stashSet(string $key, mixed $value): void
    {
        self::$stashData[static::class][$key] = $value;
    }

    public static function stashClear(): void
    {
        unset(self::$stashData[static::class]);
    }
}