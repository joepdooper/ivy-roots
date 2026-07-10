<?php

namespace Ivy\Setting\Infrastructure\Registry;

class SettingRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $definitions = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public static function define(string $key, array $config): void
    {
        $key = strtolower(str_replace(' ', '_', $key));

        static::$definitions[$key] = array_merge(
            static::$definitions[$key] ?? [],
            $config
        );
    }

    public static function has(string $key): bool
    {
        return isset(static::$definitions[$key]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        return static::$definitions[$key] ?? null;
    }
}
