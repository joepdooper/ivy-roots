<?php

namespace Ivy\Abstract;

use Delight\Auth\Auth;
use Illuminate\Container\Container;
use Ivy\Service\AuthService;
use Symfony\Component\HttpFoundation\Request;

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
            \Delight\Auth\Role::EDITOR,
            \Delight\Auth\Role::ADMIN,
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }

    public function canEditAsAdmin(): bool
    {
        return $this->auth->hasAnyRole(
            \Delight\Auth\Role::ADMIN,
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }

    public function canEditAsSuperAdmin(): bool
    {
        return $this->auth->hasRole(
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }
}
