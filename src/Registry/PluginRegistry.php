<?php

namespace Ivy\Registry;

class PluginRegistry
{
    private static array $active = [];

    public static function setActive(array $active): void
    {
        self::$active = $active;
    }

    public static function isActive(string $name): bool
    {
        return isset(self::$active[$name]);
    }

    public static function all(): array
    {
        return array_keys(self::$active);
    }
}