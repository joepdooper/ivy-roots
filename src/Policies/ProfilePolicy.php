<?php

namespace Ivy\Policies;

use Ivy\Profile;
use Ivy\User;

class ProfilePolicy
{
    public static function post(Profile $profile): bool
    {
        return User::getAuth()->isLoggedIn();
    }

    public static function index(Profile $profile): bool
    {
        return User::getAuth()->isLoggedIn();
    }
}