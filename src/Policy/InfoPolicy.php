<?php

namespace Ivy\Policy;

use Ivy\Model\Info;
use Ivy\Model\Setting;
use Ivy\Model\User;

class InfoPolicy
{
    public static function post(Info $setting): bool
    {
        return User::canEditAsAdmin();
    }

    public static function index(Info $setting): bool
    {
        return User::canEditAsAdmin();
    }
}