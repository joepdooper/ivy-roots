<?php

namespace Ivy\Manager;

use Bramus\Router\Router;

class RouterManager
{
    private static ?Router $router = null;

    public static function router(): Router
    {
        if (! self::$router) {
            self::$router = new Router;
        }

        return self::$router;
    }

    public static function register(string $method, string $pattern, string|callable $handler): void
    {
        self::router()->match($method, $pattern, self::resolve($handler));
    }

    public static function before(string $methods, string $pattern, string|callable $handler): void
    {
        self::router()->before($methods, $pattern, self::resolve($handler));
    }

    public static function mount(string $pattern, callable $fn): void
    {
        self::router()->mount($pattern, $fn);
    }

    protected static function resolve(string|callable $handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);

            return function (...$params) use ($class, $method) {
                return (new $class)->$method(...$params);
            };
        }

        throw new \InvalidArgumentException('Invalid handler');
    }
}
