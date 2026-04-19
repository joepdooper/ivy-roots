<?php

namespace Ivy\Model;

use Delight\Auth\Auth;
use Delight\Auth\Role;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Ivy\Manager\DatabaseManager;
use Ivy\Trait\HasPolicies;

class User extends Model
{
    use HasPolicies;

    private static Auth $auth;

    protected $fillable = [
        'email',
        'username',
        'status',
        'verified',
        'resettable',
        'roles_mask',
        'registered',
        'last_login',
    ];

    public static function setAuth(): void
    {
        self::$auth = new Auth(DB::connection()->getPdo());
    }

    public static function getAuth(): Auth
    {
        return self::$auth;
    }

    public static function canEditAsEditor(): bool
    {
        return self::$auth->hasAnyRole(
            Role::EDITOR,
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public static function canEditAsAdmin(): bool
    {
        return self::$auth->hasAnyRole(
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public static function canEditAsSuperAdmin(): bool
    {
        return self::$auth->hasRole(Role::SUPER_ADMIN);
    }
}
