<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\Stash;

class Setting extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'name',
        'bool',
        'value',
        'info',
        'plugin_id',
        'is_default',
    ];
}
