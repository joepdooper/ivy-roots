<?php

namespace Ivy\Shared\Infrastructure\Manager;

use Random\RandomException;

class CsrfManager
{
    /**
     * @throws RandomException
     */
    public static function token(): string
    {
        if (! SessionManager::has('csrf_token')) {
            SessionManager::set('csrf_token', bin2hex(random_bytes(32)));
        }

        return SessionManager::get('csrf_token');
    }
}
