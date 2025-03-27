<?php

namespace Ivy\Policies;

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
}