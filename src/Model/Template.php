<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\Stash;

class Template extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
