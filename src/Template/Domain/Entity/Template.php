<?php

namespace Ivy\Template\Domain\Entity;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Domain\Enum\Status;
use Ivy\Shared\Traits\HasPagination;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\HasSearching;
use Ivy\Shared\Traits\HasSorting;
use Ivy\Shared\Traits\Stash;

/**
 * @property string $name
 * @property string $package
 * @property string $interface
 * @property string $version
 * @property string $version_channel
 * @property string $description
 * @property string $type
 * @property string $url
 * @property Status $status
 */
class Template extends Entity
{
    use Stash, HasPagination, HasPolicies, HasSearching, HasSorting;

    protected $fillable = [
        'name',
        'package',
        'interface',
        'version',
        'version_channel',
        'description',
        'type',
        'url',
        'status',
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
