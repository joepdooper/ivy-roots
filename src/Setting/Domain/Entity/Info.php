<?php

namespace Ivy\Setting\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @property bool|int $is_default
 */
class Info extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'name',
        'value',
        'info',
        'plugin_id',
        'is_default',
    ];
}
