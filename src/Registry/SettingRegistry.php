<?php

namespace Ivy\Registry;

class SettingRegistry
{
    protected static array $definitions = [];

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

    public static function get(string $key): ?array
    {
        return static::$definitions[$key] ?? null;
    }
}