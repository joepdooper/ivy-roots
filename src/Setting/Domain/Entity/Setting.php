<?php

namespace Ivy\Setting\Domain\Entity;

use Ivy\Setting\Domain\Handler\SettingHandler;
use Ivy\Setting\Infrastructure\Registry\SettingRegistry;
use Ivy\Shared\Base\Entity;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

/**
 * @property string $name
 * @property bool|int $bool
 * @property string $value
 * @property string $info
 * @property int $plugin_id
 * @property bool|int $is_default
 */
class Setting extends Entity
{
    use HasPolicies, Stash;

    protected $fillable = [
        'name',
        'bool',
        'value',
        'info',
        'plugin_id',
        'is_default',
    ];

    protected static function booted(): void
    {
        static::updated(function (Setting $setting) {
            $key = strtolower(str_replace(' ', '_', $setting->name));

            $definition = SettingRegistry::get($key);

            if (! $definition) {
                return;
            }

            $handlers = (array) ($definition['handler'] ?? []);

            foreach ($handlers as $handler) {
                $instance = new $handler;
                /** @var SettingHandler $instance */
                $instance->handle(
                    setting: $setting,
                    bool: $setting->bool,
                );
            }
        });
    }
}
