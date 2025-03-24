<?php

namespace Ivy\Policies;

use Ivy\Plugin;
use Ivy\User;

class PluginPolicy
{
    public static function post(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function index(Plugin $plugin): bool
    {
        return User::canEditAsAdmin();
    }
}