<?php

namespace Ivy\Manager;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

class SessionManager
{
    private static ?Session $session = null;

    public static function getSession(): Session
    {
        if (self::$session === null) {
            $storage = new NativeSessionStorage([], new NativeFileSessionHandler());
            self::$session = new Session($storage);
        }

        return self::$session;
    }

    public static function set(string $key, mixed $value): void
    {
        self::getSession()->set($key, $value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getSession()->get($key, $default);
    }

    public static function remove(string $key): void
    {
        self::getSession()->remove($key);
    }

    public static function has(string $key): bool
    {
        return self::getSession()->has($key);
    }

    public static function getFlashBag(): \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
    {
        return self::getSession()->getFlashBag();
    }

    public static function invalidate(): void
    {
        self::getSession()->invalidate();
    }
}
