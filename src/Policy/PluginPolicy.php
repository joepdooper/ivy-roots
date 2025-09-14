<?php

namespace Ivy\Policy;

use Ivy\Model\Plugin;
use Ivy\Model\User;

class PluginPolicy
{
    public static function install(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function uninstall(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function post(Plugin $plugin): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function index(Plugin $plugin): bool
    {
        return User::canEditAsAdmin();
    }

    public static function collection(Plugin $plugin):bool
    {
        if($plugin->getInfo()->hasCollection() && User::canEditAsAdmin()){
            return true;
        }
        return false;
    }

    public static function settings(Plugin $plugin):bool
    {
        if($plugin->getInfo()->hasSettings() && User::canEditAsAdmin()){
            return true;
        }
        return false;
    }
}