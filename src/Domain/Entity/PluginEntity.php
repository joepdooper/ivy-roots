<?php

namespace Ivy\Domain\Entity;

use Illuminate\Database\Eloquent\Model;
use Ivy\Infrastructure\Helper\PluginInfo;
use Ivy\Shared\Trait\HasPolicies;

/**
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
class PluginEntity extends Model
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
