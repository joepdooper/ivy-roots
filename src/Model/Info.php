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
        'plugin_id',
        'is_default',
        'token'
    ];

    protected string $name;
    protected ?string $value = null;
    protected ?string $info = null;
    protected ?int $plugin_id = null;
    protected int $is_default;
    protected ?string $token;
}

