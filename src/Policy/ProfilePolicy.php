<?php

namespace Ivy\Policy;

use Ivy\Model\Profile;
use Ivy\Model\User;

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