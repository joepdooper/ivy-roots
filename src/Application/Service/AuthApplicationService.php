<?php

namespace Ivy\Application\Service;

use Delight\Auth\Auth;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\Domain\Entity\UserEntity;

class AuthApplicationService
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

        return UserEntity::find($userId);
    }

    public function can(string $action, $model): bool
    {
        return (bool) $model->policy($action);
    }
}
