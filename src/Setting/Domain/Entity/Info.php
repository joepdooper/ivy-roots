<?php

namespace Ivy\Setting\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static select(string ...$columns)
 * @method static static find(int $id)
 * @method static static first()
 * @method static static pluck(string $column, ?string $key = null)
 * @method static static value(string $column)
 * @method static static create(array $attributes)
 * @method static static handle(Info $info, bool $bool)
 * @method static static get()
 * @method static static all()
 *
 * @property string $name
 * @property string $value
 * @property string $info
 * @property int $plugin_id
 * @property bool|int $is_default
 */
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
}
