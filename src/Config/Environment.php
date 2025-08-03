<?php

namespace Ivy\Config;

class Environment
{
    public static function isDev(): bool
    {
        return $_ENV['APP_ENV'] === 'development';
    }

    public static function isProd(): bool
    {
        return $_ENV['APP_ENV'] === 'production';
    }
}
