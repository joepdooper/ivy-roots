<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;

class Template extends Model
{
    protected string $table = 'templates';

    protected string $path = 'admin/template';

    protected array $columns = [
        'type',
        'value',
    ];

    protected string $type;

    protected string $value;
}
