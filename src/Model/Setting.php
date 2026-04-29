<?php

namespace Ivy\Model;

use Illuminate\Database\Eloquent\Model;
use Ivy\Registry\SettingRegistry;
use Ivy\Trait\HasPolicies;
use Ivy\Trait\Stash;

class Setting extends Model
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

    protected static function booted()
    {
        static::updated(function (Setting $setting) {
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
