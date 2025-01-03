<?php

namespace Ivy;

class Setting extends Model
{
    use Stash;

    protected string $table = 'setting';
    protected string $path = _BASE_PATH . 'admin/setting';
    protected array $columns = [
        'name',
        'bool',
        'value',
        'info',
    ];

    public string $name;
    public int $bool;
    public string $value;
    public string $info;
}
