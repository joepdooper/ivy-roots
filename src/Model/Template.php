<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;

/**
 * @property string $type
 * @property string $value
 */
class Template extends Model
{
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
