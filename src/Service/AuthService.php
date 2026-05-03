<?php

namespace Ivy\Service;

use Delight\Auth\Auth;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\Model\User;

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
    public function user(): ?User
    {
        if (! $this->auth->isLoggedIn()) {
            return null;
        }

        $userId = $this->auth->getUserId();

        return User::find($userId);
    }

}
