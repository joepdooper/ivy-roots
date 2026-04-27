<?php

namespace Ivy\Policy;

use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\User;

class PluginPolicy
{
    public static function index(Plugin $plugin): bool
    {
        return User::canEditAsAdmin();
    }

    public static function sync(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function install(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function uninstall(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function update(Plugin $plugin): bool
    {
        return User::canEditAsAdmin();
    }

    public static function collection(Plugin $plugin): bool
    {
        if ($plugin->info->hasCollection() && User::canEditAsAdmin()) {
            return true;
        }

        return false;
    }

    public static function settings(Plugin $plugin): bool
    {
        if ($plugin->info->hasSettings() && User::canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
