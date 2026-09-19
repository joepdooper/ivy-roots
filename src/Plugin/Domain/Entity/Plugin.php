<?php

namespace Ivy\Plugin\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Domain\Enum\Status;
use Ivy\Shared\Traits\HasPolicies;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $package
 * @property string $interface
 * @property string $version
 * @property string $version_channel
 * @property string $description
 * @property string $type
 * @property string $url
 * @property Status $status
 * @property bool $active
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
        'url',
        'status',
        'active',
    ];

    protected $casts = [
        'status' => Status::class,
    ];
}
