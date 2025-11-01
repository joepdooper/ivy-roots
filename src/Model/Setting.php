<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\Trait\Stash;

class Setting extends Model
{
    use Stash;

    protected string $table = 'settings';
    protected string $path = 'admin/setting';
    protected array $columns = [
        'name',
        'bool',
        'value',
        'info',
        'plugin_id',
        'is_default',
        'token'
    ];

    protected string $name;
    protected int $bool;
    protected ?string $value = null;
    protected ?string $info = null;
    protected ?int $plugin_id = null;
    protected int $is_default;
    protected ?string $token;
}
