<?php

namespace Ivy\User\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Traits\HasPolicies;

/**
 * @property int $id
 * @property string $email
 * @property string $username
 * @property int $status
 * @property int $verified
 * @property int $resettable
 * @property int $roles_mask
 * @property int $registered
 * @property int $last_login
 */
class User extends Entity
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
}
