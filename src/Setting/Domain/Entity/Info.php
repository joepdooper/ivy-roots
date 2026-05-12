<?php

namespace Ivy\Setting\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

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
