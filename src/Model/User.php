<?php

namespace Ivy\Model;

use Delight\Auth\Auth;
use Delight\Auth\Role;
use Ivy\Abstract\Model;
use Ivy\Manager\DatabaseManager;

class User extends Model
{

    protected string $table = 'users';
    protected string $path = 'admin/user';
    protected array $columns = [
        'email',
        'username',
        'status',
        'verified',
        'resettable',
        'roles_mask',
        'registered',
        'last_login'
    ];

    private static Auth $auth;

    protected string $email;
    protected string $username;
    protected int $status;
    protected int $verified;
    protected int $resettable;
    protected int $roles_mask;
    protected int $registered;
    protected ?int $last_login;

    static function canEditAsEditor(): bool
    {
        $roles = [
            Role::EDITOR,
            Role::ADMIN,
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    static function canEditAsAdmin(): bool
    {
        $roles = [
            Role::ADMIN,
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    static function canEditAsSuperAdmin(): bool
    {
        $roles = [
            Role::SUPER_ADMIN
        ];
        return self::$auth->hasAnyRole(...$roles);
    }

    static function setAuth(): void
    {
        self::$auth = new Auth(DatabaseManager::connection(), true);
    }

    public static function getAuth(): ?Auth
    {
        return self::$auth;
    }
}
