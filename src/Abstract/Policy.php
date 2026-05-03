<?php

namespace Ivy\Abstract;

use Delight\Auth\Auth;
use Ivy\Service\AuthService;

abstract class Policy
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function canEditAsEditor(): bool
    {
        return $this->authService->auth()->hasAnyRole(
            \Delight\Auth\Role::EDITOR,
            \Delight\Auth\Role::ADMIN,
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }

    public function canEditAsAdmin(): bool
    {
        return $this->authService->auth()->hasAnyRole(
            \Delight\Auth\Role::ADMIN,
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }

    public function canEditAsSuperAdmin(): bool
    {
        return $this->authService->auth()->hasRole(
            \Delight\Auth\Role::SUPER_ADMIN
        );
    }
}
