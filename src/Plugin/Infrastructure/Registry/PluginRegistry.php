<?php

namespace Ivy\Plugin\Infrastructure\Registry;

class PluginRegistry
{
    /**
     * @var array<string, mixed>
     */
    private static array $active = [];

    /**
     * @param  array<string, mixed>  $active
     */
    public static function setActive(array $active): void
    {
        self::$active = $active;
    }

    public static function isActive(string $name): bool
    {
        return isset(self::$active[$name]);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::$active);
    }
}
