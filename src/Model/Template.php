<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\App;
use Ivy\Core\Language;
use Ivy\Core\Path;
use Latte\Engine;

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
