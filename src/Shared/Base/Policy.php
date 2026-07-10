<?php

namespace Ivy\Shared\Base;

use Ivy\User\Application\Service\AuthService;

abstract class Policy
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function isLoggedIn(): bool
    {
        return $this->authService->isLoggedIn();
    }

    public function canEditAsEditor(): bool
    {
        return $this->authService->canEditAsEditor();
    }

    public function canEditAsAdmin(): bool
    {
        return $this->authService->canEditAsAdmin();
    }

    public function canEditAsSuperAdmin(): bool
    {
        return $this->authService->canEditAsSuperAdmin();
    }
}
