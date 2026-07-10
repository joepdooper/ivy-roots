<?php

namespace Ivy\Setting\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @property string $name
 * @property string $value
 * @property string $info
 * @property int $plugin_id
 * @property bool|int $is_default
 */
class Info extends Entity
{
    use HasPolicies, Stash;

    protected $fillable = [
        'name',
        'value',
        'info',
        'plugin_id',
        'is_default',
    ];
}
