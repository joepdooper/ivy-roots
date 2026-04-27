<?php

namespace Ivy\Manager;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\Core\Contracts\PluginInterface;
use Ivy\Core\Language;
use Ivy\Form\PluginInfoForm;
use Ivy\Helper\PluginInfoLoader;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Helper\PluginHelper;

class PluginManager
{
    public function __construct(
        private Plugin $plugin
    ) {}

    private function resolvePluginInterface(): PluginInterface
    {
        $class = $this->plugin->interface;

        if (!class_exists($class)) {
            throw new Exception("Plugin class not found: {$class}");
        }

        $instance = new $class();

        if (!$instance instanceof PluginInterface) {
            throw new Exception("Plugin must implement PluginInterface: {$class}");
        }

        return $instance;
    }

    public function install(): array
    {
        $this->plugin->authorize('install');

        $info = (new PluginInfoLoader)->load($this->plugin->url);
        $result = (new PluginInfoForm)->validate($info);

        if(!$result->valid) {
            return [
                'status' => 'error',
                'message' => 'Invalid info.json: ' . implode(', ', $result->errors),
            ];
        }

        $this->plugin->fill($result->data);

        if (isset($info['dependencies']) && is_array($info['dependencies'])) {
            $missing = PluginHelper::getMissingDependencies($info['dependencies']);
            if (!empty($missing)) {
                return [
                    'status' => 'warning',
                    'message' => 'Missing dependencies: ' . implode(', ', $missing),
                ];
            }
        }

        $this->resolvePluginInterface()->install();

        try {
            Capsule::transaction(function () use($info) {
                $this->plugin->save();
                if (isset($info['settings'])) {
                    foreach ($info['settings'] as $setting) {
                        new Setting()->fill([
                            ...$setting,
                            'plugin_id' => $this->plugin->id,
                            'is_default' => 1,
                        ])->save();
                    }
                }
                if (isset($info['collection']) && !empty($info['collection'])) {
                    (new PluginCollectionManager($this->plugin))->install();
                }
            });
        } catch (Exception $e) {
            $this->resolvePluginInterface()->uninstall();
            return [
                'status' => 'error',
                'message' => 'Error installing plugin: ' . $e->getMessage()
            ];
        }

        return [
            'status' => 'success',
            'message' => Language::translate(
                'plugin.installed_successfully',
                ['plugin' => $this->plugin->name]
            )
        ];
    }

    public function uninstall(): array
    {
        $this->plugin->authorize('uninstall');

        $info = (new PluginInfoLoader)->load($this->plugin->url);
        $result = (new PluginInfoForm)->validate($info);

        if(!$result->valid) {
            return [
                'status' => 'error',
                'message' => 'Invalid info.json: ' . implode(', ', $result->errors),
            ];
        }

        try {
            Capsule::transaction(function () use ($info) {
                if (isset($info['collection']) && !empty($info['collection'])) {
                    (new PluginCollectionManager($this->plugin))->uninstall();
                }
                Setting::where('plugin_id', $this->plugin->id)->delete();
                $this->plugin->delete();
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error uninstalling plugin: ' . $e->getMessage()
            ];
        }

        $this->resolvePluginInterface()->uninstall();

        return [
            'status' => 'success',
            'message' => Language::translate(
                'plugin.uninstalled_successfully',
                ['plugin' => $this->plugin->name]
            )
        ];
    }
}