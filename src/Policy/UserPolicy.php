<?php

namespace Ivy\Policy;

use Ivy\Model\User;

class UserPolicy
{
    public static function post(User $user): bool
    {
        return User::canEditAsSuperAdmin();
    }

    public static function index(User $user): bool
    {
        return User::canEditAsAdmin();
    }
}