<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\Stash;

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

    protected function getPath(): string
    {
        return 'admin/info';
    }
}
