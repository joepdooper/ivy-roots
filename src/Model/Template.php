<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\Trait\HasDirtyChecking;
use Ivy\Trait\Stash;

/**
 * @property string $type
 * @property string $value
 */
class Template extends Model
{
    use HasDirtyChecking;

    protected string $table = 'templates';

    protected string $path = 'admin/template';

    /** @var string[] */
    protected array $columns = [
        'type',
        'value',
    ];

    protected string $type;

    protected string $value;
}
