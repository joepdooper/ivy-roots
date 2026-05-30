<?php

namespace Ivy\Shared\Base;

use Delight\Auth\Auth;
use Delight\Auth\Role;

abstract class Policy
{
    protected Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function isLoggedIn(): bool
    {
        return $this->auth->isLoggedIn();
    }

    public function canEditAsEditor(): bool
    {
        return $this->auth->hasAnyRole(
            Role::EDITOR,
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public function canEditAsAdmin(): bool
    {
        return $this->auth->hasAnyRole(
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public function canEditAsSuperAdmin(): bool
    {
        return $this->auth->hasRole(
            Role::SUPER_ADMIN
        );
    }
}
