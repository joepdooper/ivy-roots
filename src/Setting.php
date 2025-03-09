<?php

namespace Ivy;

class Setting extends Model
{
    use Stash;

    protected string $table = 'setting';
    protected string $path = 'admin/setting';
    protected array $columns = [
        'name',
        'bool',
        'value',
        'info'
    ];

    protected string $name;
    protected int $bool;
    protected ?string $value = null;
    protected ?string $info = null;
}
