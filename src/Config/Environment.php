<?php

namespace Ivy\Config;

class Environment
{
    public static function isDev(): bool
    {
        return getenv('APP_ENV') === 'development';
    }

    public static function isProd(): bool
    {
        return getenv('APP_ENV') === 'production';
    }
}
