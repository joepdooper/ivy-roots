<?php

namespace Ivy\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Infrastructure\Registry\SettingRegistry;
use Ivy\Shared\Traits\HasPolicies;
use Ivy\Shared\Traits\Stash;

class SettingModel extends Model
{
    use Stash, HasPolicies;

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
        static::updated(function (SettingModel $setting) {
            $key = strtolower(str_replace(' ', '_', $setting->name));

            $definition = SettingRegistry::get($key);

            if (!$definition) {
                return;
            }

            $handlers = (array) ($definition['handler'] ?? []);

            foreach ($handlers as $handler) {
                $instance = new $handler();
                $instance->handle(
                    setting: $setting,
                    bool: $setting->bool,
                );
            }
        });
    }
}
