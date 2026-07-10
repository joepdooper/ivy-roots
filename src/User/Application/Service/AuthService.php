<?php

namespace Ivy\User\Application\Service;

use Delight\Auth\Auth;
use Delight\Auth\Role;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\User\Domain\Entity\Profile;
use Ivy\User\Domain\Entity\User;

class AuthService
{
    private Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth(
            Capsule::connection()->getPdo()
        );
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function isLoggedIn(): bool
    {
        return $this->auth->isLoggedIn();
    }

    /**
     * Bridge: Delight userId → Eloquent User
     */
    public function authUser(): ?User
    {
        if (! $this->auth->isLoggedIn()) {
            return null;
        }

        $userId = $this->auth->getUserId();

        return User::find($userId);
    }

    public function authProfile(): ?Profile
    {
        if (! $this->auth->isLoggedIn()) {
            return null;
        }

        $userId = $this->auth->getUserId();

        return Profile::where('user_id', $userId)->first();
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

    public function can(string $action, mixed $model): bool
    {
        return (bool) $model->policy($action);
    }
}
