<?php

namespace Ivy\Policies;

use Ivy\User;
use Ivy\Plugin;

class PluginPolicy
{
    public static function post(Plugin $plugin)
    {
        return User::canEditAsSuperAdmin();
    }
}