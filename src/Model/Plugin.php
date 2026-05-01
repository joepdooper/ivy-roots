<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Helper\PluginInfo;
use Ivy\Trait\HasPolicies;

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