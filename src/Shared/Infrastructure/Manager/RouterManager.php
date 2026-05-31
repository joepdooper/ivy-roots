<?php

namespace Ivy\Shared\Infrastructure\Manager;

use Bramus\Router\Router;

class RouterManager
{
    private static ?Router $router = null;

    private static array $errorHandlers = [];

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

        if (str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);

            return function (...$params) use ($class, $method) {
                return (new $class)->$method(...$params);
            };
        }

        throw new \InvalidArgumentException('Invalid handler');
    }

    public static function error(int $code, callable $handler): void
    {
        self::$errorHandlers[$code] = $handler;
    }

    public static function triggerError(int $code, string $message): void
    {
        if (isset(self::$errorHandlers[$code])) {
            call_user_func(self::$errorHandlers[$code], $message);
            return;
        }

        http_response_code($code);
    }
}
