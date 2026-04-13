<?php

namespace Ivy\Policy;

use Ivy\Model\Setting;
use Ivy\Model\User;

class UserPolicy
{
    public static function index(User $user): bool
    {
        return User::canEditAsAdmin();
    }

    public static function sync(User $user): bool
    {
        return User::canEditAsAdmin();
    }

    public static function save(User $user): bool
    {
        return User::canEditAsAdmin();
    }

    public static function add(User $user): bool
    {
        return User::canEditAsAdmin();
    }

    public static function update(User $user): bool
    {
        return User::canEditAsAdmin();
    }

    public static function delete(User $user): bool
    {
        return User::canEditAsAdmin();
    }
}
