<?php

namespace Ivy\Plugin\Domain\Entity;

use Ivy\Plugin\Domain\Enum\PluginStatus;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfo;
use Ivy\Shared\Base\Entity;
use Ivy\Shared\Traits\HasPolicies;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $package
 * @property string $interface
 * @property string $version
 * @property string $description
 * @property string $type
 * @property PluginStatus $status
 * @property bool $active
 * @property string $url
 */
class Plugin extends Entity
{
    use HasPolicies;

    protected $fillable = [
        'parent_id',
        'name',
        'package',
        'interface',
        'version',
        'version_channel',
        'description',
        'type',
        'status',
        'active',
        'url',
    ];

    protected $casts = [
        'status' => PluginStatus::class,
    ];

    public PluginInfo $info;
}
