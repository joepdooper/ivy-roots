<?php

namespace Ivy\Policies;

use Ivy\Setting;
use Ivy\User;

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
}