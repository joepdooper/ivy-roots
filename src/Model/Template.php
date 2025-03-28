<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\App;
use Ivy\Language;
use Ivy\Path;
use Latte\Engine;

class Template extends Model
{
    protected string $table = 'template';
    protected string $path = 'admin/template';
    protected array $columns = [
        'type',
        'value',
    ];

    protected string $type;
    protected string $value;
}
