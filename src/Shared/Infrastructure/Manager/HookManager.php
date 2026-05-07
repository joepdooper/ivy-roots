<?php

namespace Ivy\Infrastructure\Manager;

use Hooks;

class HookManager
{
    protected static ?Hooks $hooks = null;

    public static function instance(): Hooks
    {
        return self::$hooks ??= new Hooks;
    }

    /**
     * @param callable():mixed $function_to_add
     */
    public static function add(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1): void
    {
        self::instance()->add_action($tag, $function_to_add, $priority, $accepted_args);
    }

    public static function do(string $tag, mixed ...$args): mixed
    {
        return self::instance()->do_action($tag, ...$args);
    }
}
