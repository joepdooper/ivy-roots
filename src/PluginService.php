<?php

namespace Ivy;

use Exception;

class PluginService
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function install(): void
    {
        $this->plugin->setInfo();

        try {
            if (!empty($missing = PluginDependencyChecker::getMissingDependencies($this->plugin->getInfo()->getDependencies()))) {
                $count = count($missing);
                $message = "This plugin has " . ($count > 1 ? "dependencies" : "dependency") . ". Please install the " . ($count > 1 ? "plugins" : "plugin") . " " . implode(", ", $missing);
                Message::add($message, Path::get('BASE_PATH') . 'admin/plugin');
            }

            if (isset($this->plugin->getInfo()->getDatabase()['install']) && !empty($this->plugin->getInfo()->getDatabase()['install']) && !empty($this->plugin->getInfo()->getUrl())) {
                $installPath = Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $this->plugin->getInfo()->getUrl() . DIRECTORY_SEPARATOR . $this->plugin->getInfo()->getDatabase()['install'];
                if (file_exists($installPath)) {
                    require_once $installPath;
                }
            }

            $this->plugin->setId($this->plugin->insert());

            if (!empty($this->plugin->getInfo()->getCollection())) {
                (new PluginCollectionHandler($this->plugin))->install();
            }
        } catch (Exception $e) {
            Message::add("Error installing plugin: " . $e->getMessage(), _BASE_PATH . 'admin/plugin');
        }
    }

    public function uninstall(): void
    {
        $this->plugin->setInfo();

        try {
            if (!empty($this->plugin->getInfo()->getCollection())) {
                (new PluginCollectionHandler($this->plugin))->uninstall();
            }

            if (isset($this->plugin->getInfo()->getDatabase()['uninstall']) && !empty($this->plugin->getInfo()->getDatabase()['uninstall'])) {
                $uninstallPath = Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $this->plugin->getInfo()->getUrl() . DIRECTORY_SEPARATOR . $this->plugin->getInfo()->getDatabase()['uninstall'];
                if (file_exists($uninstallPath)) {
                    require_once $uninstallPath;
                }
            }

            $this->plugin->delete();
        } catch (Exception $e) {
            Message::add("Error uninstalling plugin: " . $e->getMessage(), Path::get('BASE_PATH') . 'admin/plugin');
        }
    }
}

