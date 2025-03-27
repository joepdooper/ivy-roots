<?php

namespace Ivy\Policies;


use Ivy\Model\Plugin;
use Ivy\Model\User;

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