<?php

namespace Ivy\Model;

use Ivy\Abstract\Model;
use Ivy\Trait\HasDirtyChecking;
use Ivy\Trait\Stash;

/**
 * @property int|null $plugin_id
 * @property string|null $name
 * @property int $bool
 * @property string|null $value
 * @property string|null $info
 * @property int $is_default
 * @property string|null $token
 */
class Setting extends Model
{
    use Stash, HasDirtyChecking;

    protected string $table = 'settings';

    protected string $path = 'admin/setting';

    /** @var string[] */
    protected array $columns = [
        'name',
        'bool',
        'value',
        'info',
        'plugin_id',
        'is_default',
        'token',
    ];

    protected string $name;

    protected int $bool = 0;

    protected ?string $value = null;

    protected ?string $info = null;

    protected ?int $plugin_id = null;

    protected int $is_default = 0;

    protected ?string $token = null;
}
