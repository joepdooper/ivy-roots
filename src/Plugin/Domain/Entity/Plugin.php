<?php

namespace Ivy\Plugin\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Domain\Enum\Status;
use Ivy\Shared\Traits\HasPagination;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\HasSearching;
use Ivy\Shared\Traits\HasSorting;

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
    use HasPagination, HasPolicies, HasSearching, HasSorting;

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

    protected static array $sortable = [
        'name',
        'package',
        'type',
    ];

    protected static array $searchable = [
        'name',
        'package',
        'type',
    ];
}
