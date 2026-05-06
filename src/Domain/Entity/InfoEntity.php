<?php

namespace Ivy\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Trait\HasPolicies;
use Ivy\Shared\Trait\Stash;

class InfoEntity extends Model
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
