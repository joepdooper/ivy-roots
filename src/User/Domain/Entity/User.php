<?php

namespace Ivy\User\Domain\Entity;

use Delight\Auth\Role;
use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static find(int $id)
 * @method static static first()
 */
class User extends Model
{
    use HasPolicies;

    public $timestamps = false;

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

    public function hasRole(int $role): bool
    {
        return ($this->roles_mask & $role) !== 0;
    }

    public function hasAnyRole(int ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function canEditAsEditor(): bool
    {
        return $this->hasAnyRole(
            Role::EDITOR,
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public function canEditAsAdmin(): bool
    {
        return $this->hasAnyRole(
            Role::ADMIN,
            Role::SUPER_ADMIN
        );
    }

    public function canEditAsSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }
}
