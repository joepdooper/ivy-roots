<?php

namespace Ivy\Infrastructure\Manager;

class CsrfManager
{
    public static function token(): string
    {
        if (!SessionManager::has('csrf_token')) {
            SessionManager::set('csrf_token', bin2hex(random_bytes(32)));
        }

        return SessionManager::get('csrf_token');
    }
}
