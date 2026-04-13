<?php

namespace Ivy\Policy;

use Ivy\Model\Info;
use Ivy\Model\User;

class InfoPolicy
{
    public static function index(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function sync(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function save(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function add(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function update(Info $info): bool
    {
        return User::canEditAsAdmin();
    }

    public static function delete(Info $info): bool
    {
        if (! $info->is_default && User::canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
