<?php

namespace Ivy;

use Exception;

class PluginService
{
    private PluginInfo $pluginInfo;

    public function install(Plugin $plugin): void
    {
        try {
            $this->pluginInfo = $plugin->setInfo()->getInfo();
            if (!empty($missing = PluginDependencyChecker::getMissingDependencies($this->pluginInfo->getDependencies()))) {
                $count = count($missing);
                $message = "This plugin has " . ($count > 1 ? "dependencies" : "dependency") . ". Please install the " . ($count > 1 ? "plugins" : "plugin") . " " . implode(", ", $missing);
                Message::add($message, _BASE_PATH . 'admin/plugin');
            }
            if (isset($this->pluginInfo->getDatabase()['install']) && !empty($this->pluginInfo->getDatabase()['install']) && !empty($this->pluginInfo->getUrl())) {
                if (file_exists(_PUBLIC_PATH . _PLUGIN_PATH . $this->pluginInfo->getUrl() . DIRECTORY_SEPARATOR . $this->pluginInfo->getDatabase()['install'])) {
                    require_once _PUBLIC_PATH . _PLUGIN_PATH . $this->pluginInfo->getUrl() . DIRECTORY_SEPARATOR . $plugin->getInfo()->getDatabase()['install'];
                }
            }
            $plugin->setId($plugin->insert());

            if (!empty($this->pluginInfo->getCollection())) {
                (new PluginCollectionHandler($plugin))->install();
            }
        } catch (Exception $e) {
            Message::add("Error installing plugin: " . $e->getMessage(), _BASE_PATH . 'admin/plugin');
        }
    }

    public function uninstall(Plugin $plugin): void
    {
        try {
            $this->pluginInfo = $plugin->setInfo()->getInfo();
            if (!empty($this->pluginInfo->getCollection())) {
                (new PluginCollectionHandler($plugin))->uninstall();
            }
            if (isset($this->pluginInfo->getDatabase()['uninstall']) && !empty($this->pluginInfo->getDatabase()['uninstall'])) {
                if (file_exists(_PUBLIC_PATH . _PLUGIN_PATH . $this->pluginInfo->getUrl() . DIRECTORY_SEPARATOR . $this->pluginInfo->getDatabase()['uninstall'])) {
                    require_once _PUBLIC_PATH . _PLUGIN_PATH . $this->pluginInfo->getUrl() . DIRECTORY_SEPARATOR . $this->pluginInfo->getDatabase()['uninstall'];
                }
            }
            $plugin->delete();
        } catch (Exception $e) {
            Message::add("Error uninstalling plugin: " . $e->getMessage(), _BASE_PATH . 'admin/plugin');
        }
    }
}
