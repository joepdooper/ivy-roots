<?php

namespace Ivy\Policy;

use Ivy\Model\Info;
use Ivy\Model\Setting;
use Ivy\Model\User;

class InfoPolicy
{
    public static function post(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function index(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function delete(Info $info): bool
    {
        if(!$info->is_default && User::canEditAsAdmin()){
            return true;
        }
        return false;
    }
}