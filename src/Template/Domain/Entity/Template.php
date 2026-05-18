<?php

namespace Ivy\Template\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static select(string ...$columns)
 * @method static static find(int $id)
 * @method static static first()
 *
 * @property string $type
 * @property string $value
 */
class Template extends Model
{
    use Stash, HasPolicies;

    protected $fillable = [
        'type',
        'value',
    ];
}
