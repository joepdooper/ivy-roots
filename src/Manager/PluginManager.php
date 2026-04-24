<?php

namespace Ivy\Manager;

use Exception;
use Ivy\Core\Contracts\PluginInterface;
use Ivy\Core\Language;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Helper\PluginHelper;

class PluginManager
{
    public function __construct(
        private Plugin $pluginModel
    ) {}

    /**
     * Resolve plugin instance from class name stored in metadata/DB
     */
    private function resolvePlugin(): PluginInterface
    {
        $class = $this->pluginModel->class
            ?? $this->pluginModel->getInfo()->getMainClass();

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
        $this->pluginModel->authorize('install');
        $this->pluginModel->setInfo();

        try {
            $missing = PluginHelper::getMissingDependencies(
                $this->pluginModel->getInfo()->getDependencies()
            );

            if (!empty($missing)) {
                return [
                    'status' => 'warning',
                    'message' => 'Missing dependencies: ' . implode(', ', $missing),
                ];
            }

            $plugin = $this->resolvePlugin();
            $plugin->install();

            $this->pluginModel->save();

            $this->installSettings();

            // $this->installCollections();

            return [
                'status' => 'success',
                'message' => Language::translate(
                    'plugin.installed_successfully',
                    ['plugin' => $this->pluginModel->name]
                )
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error installing plugin: ' . $e->getMessage()
            ];
        }
    }

    public function uninstall(): array
    {
        $this->pluginModel->authorize('uninstall');
        $this->pluginModel->setInfo();

        try {
            $plugin = $this->resolvePlugin();
            $plugin->uninstall();

            $this->uninstallCollections();

            $this->pluginModel
                ->getRelation('settings')
                ?->deleteAll();

            $this->pluginModel->delete();

            return [
                'status' => 'success',
                'message' => Language::translate(
                    'plugin.uninstalled_successfully',
                    ['plugin' => $this->pluginModel->name]
                )
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error uninstalling plugin: ' . $e->getMessage()
            ];
        }
    }

    public function enable(): void
    {
        $plugin = $this->resolvePlugin();

        $plugin->enable();
        $this->pluginModel->update(['active' => 1]);
    }

    public function disable(): void
    {
        $plugin = $this->resolvePlugin();

        $plugin->disable();
        $this->pluginModel->update(['active' => 0]);
    }

    public function register(): void
    {
        $plugin = $this->resolvePlugin();
        $plugin->register();
    }

    public function boot(): void
    {
        $plugin = $this->resolvePlugin();
        $plugin->boot();
    }

    private function installSettings(): void
    {
        if (!$this->pluginModel->getInfo()->hasSettings()) {
            return;
        }

        foreach ($this->pluginModel->getInfo()->getSettings() as $setting) {
            (new Setting)->populate([
                ...$setting,
                'plugin_id' => $this->pluginModel->id,
                'is_default' => 1,
            ])->insert();
        }
    }

    private function installCollections(): void
    {
        if ($this->pluginModel->getInfo()->hasCollection()) {
            (new PluginCollectionManager($this->pluginModel))->install();
        }
    }

    private function uninstallCollections(): void
    {
        if ($this->pluginModel->getInfo()->hasCollection()) {
            (new PluginCollectionManager($this->pluginModel))->uninstall();
        }
    }
}