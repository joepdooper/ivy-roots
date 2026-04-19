<?php

namespace Ivy\Trait;

trait Stash
{
    private static array $stashData = [];

    public static function stash(): static
    {
        $type = static::class;

        if (!isset(self::$stashData[$type])) {
            self::$stashData[$type] = $type::all();
        }

        return new $type;
    }

    public function keyByColumn(string $column): void
    {
        $type = static::class;

        if (!isset(self::$stashData[$type])) {
            throw new \RuntimeException('Call stash() before keyByColumn().');
        }

        self::$stashData[$type] = self::$stashData[$type]
            ->keyBy($column)
            ->mapWithKeys(function ($item) use ($column) {
                $value = $item->{$column};
                $key = strtolower(str_replace(' ', '_', $value));
                return [$key => $item];
            })
            ->all();
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
