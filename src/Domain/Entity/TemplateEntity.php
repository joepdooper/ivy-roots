<?php

namespace Ivy\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Trait\HasPolicies;
use Ivy\Shared\Trait\Stash;

class TemplateEntity extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
