<?php

namespace Ivy\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

class TemplateModel extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
