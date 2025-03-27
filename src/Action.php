<?php

namespace Ivy;

use Hooks;

class Action
{
    protected static ?Hooks $hooks = null;

    public static function instance(): Hooks
    {
        return self::$hooks ??= new Hooks();
    }

    public static function add(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1): void
    {
        self::instance()->add_action($tag, $function_to_add, $priority, $accepted_args);
    }

    public static function do(string $tag, mixed ...$args): mixed
    {
        return self::instance()->do_action($tag, ...$args);
    }
}
