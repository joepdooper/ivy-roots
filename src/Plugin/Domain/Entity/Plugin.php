<?php

namespace Ivy\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Infrastructure\Helper\PluginInfo;
use Ivy\Shared\Traits\HasPolicies;

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
class PluginModel extends Model
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
