<?php

namespace Ivy\Template\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @property string $type
 * @property string $value
 */
class Template extends Entity
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
