<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\Trait\Stash;

class Info extends Model
{
    use Stash;

    protected string $table = 'infos';
    protected string $path = 'admin/info';
    protected array $columns = [
        'name',
        'value',
        'info',
        'plugin_id'
    ];

    protected string $name;
    protected ?string $value = null;
    protected ?string $info = null;
    protected ?int $plugin_id = null;
}
