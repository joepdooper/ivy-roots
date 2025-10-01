<?php

namespace Ivy\Policy;

use Ivy\Model\Setting;
use Ivy\Model\User;

class SettingPolicy
{
    public static function post(Setting $setting): bool
    {
        return User::canEditAsAdmin();
    }

    public static function index(Setting $setting): bool
    {
        return User::canEditAsAdmin();
    }

    public static function delete(Setting $setting): bool
    {
        if(!$setting->is_default && User::canEditAsAdmin()){
            return true;
        }
        return false;
    }
}