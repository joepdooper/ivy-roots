<?php

namespace Ivy;

use Delight\Auth\Administration;
use Delight\Auth\AttemptCancelledException;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\Role;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use Exception;
use Hooks;
use HTMLPurifier_Config;
use HTMLPurifier;
use function urlencode;

class User extends Model
{

    protected string $table = 'users';
    protected string $path = 'admin/user';
    protected array $columns = [
        'email',
        'username',
        'users_image',
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
    protected string $users_image;
    protected int $status;
    protected int $verified;
    protected int $resettable;
    protected int $roles_mask;
    protected int $registered;
    protected int $last_login;

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

    /**
     * @throws UnknownIdException
     */
    static function userIsSuperAdmin($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::SUPER_ADMIN);
    }

    /**
     * @throws UnknownIdException
     */
    static function userIsAdmin($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::ADMIN);
    }

    /**
     * @throws UnknownIdException
     */
    static function userIsEditor($id): bool
    {
        return self::$auth->admin()->doesUserHaveRole($id, Role::EDITOR);
    }

    static function setAuth(): void
    {
        self::$auth = new Auth(DB::getConnection(), true);
    }

    public static function getAuth(): ?Auth
    {
        return self::$auth;
    }
}
