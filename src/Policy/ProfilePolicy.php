<?php

namespace Ivy\Policy;

use Ivy\Model\Profile;
use Ivy\Model\User;

class ProfilePolicy
{
    public static function index(Profile $profile): bool
    {
        return User::getAuth()->isLoggedIn();
    }

    public static function sync(Profile $profile): bool
    {
        return User::getAuth()->isLoggedIn();
    }

    public static function save(Profile $profile): bool
    {
        return User::canEditAsAdmin();
    }

    public static function add(Profile $profile): bool
    {
        return User::canEditAsAdmin();
    }

    public static function update(Profile $profile): bool
    {
        return User::canEditAsAdmin();
    }

    public static function delete(Profile $profile): bool
    {
        if (! $info->is_default && User::canEditAsAdmin()) {
            return true;
        }

        return false;
    }
}
