<?php

namespace Ivy\Plugin\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfo;
use Ivy\Shared\Traits\HasPolicies;

/**
 * @method static static where(string $column, mixed $value = null)
 * @method static static create(array $attributes)
 * @method static static find(int $id))
 * @method static static all
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $interface
 * @property string $version
 * @property string $description
 * @property string $type
 * @property bool $active
 * @property string $url
 */
class Plugin extends Model
{
    use HasPolicies;
    
    protected $fillable = [
        'parent_id',
        'name',
        'interface',
        'version',
        'description',
        'type',
        'active',
        'url',
    ];

    public PluginInfo $info;
}
