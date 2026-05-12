<?php

namespace Ivy\Template\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

class Template extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
