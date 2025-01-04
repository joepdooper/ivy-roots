<?php

namespace Ivy;

use Exception;

class PluginService
{
    public function install(Plugin $plugin): void
    {
        try {
            $plugin->setInfo();
            if (!empty($missing = PluginDependencyChecker::getMissingDependencies($plugin->getInfo()->dependencies))) {
                $count = count($missing);
                $message = "This plugin has " . ($count > 1 ? "dependencies" : "dependency") . ". Please install the " . ($count > 1 ? "plugins" : "plugin") . " " . implode(", ", $missing);
                Message::add($message, _BASE_PATH . 'admin/plugin');
            }
            if (isset($plugin->getInfo()->database['install']) && !empty($plugin->getInfo()->database['install']) && !empty($plugin->getInfo()->url)) {
                if (file_exists(_PUBLIC_PATH . _PLUGIN_PATH . $plugin->getInfo()->url . DIRECTORY_SEPARATOR . $plugin->getInfo()->database['install'])) {
                    require_once _PUBLIC_PATH . _PLUGIN_PATH . $plugin->getInfo()->url . DIRECTORY_SEPARATOR . $plugin->getInfo()->database['install'];
                }
            }
            $plugin->setId($plugin->insert());

            if (!empty($plugin->getInfo()->collection)) {
                (new PluginCollectionHandler($plugin))->install();
            }
        } catch (Exception $e) {
            Message::add("Error installing plugin: " . $e->getMessage(), _BASE_PATH . 'admin/plugin');
        }
    }

    public function uninstall(Plugin $plugin): void
    {
        try {
            $plugin->setInfo();
            if (!empty($plugin->getInfo()->collection)) {
                (new PluginCollectionHandler($plugin))->uninstall();
            }
            if (isset($plugin->getInfo()->database['uninstall']) && !empty($plugin->getInfo()->database['uninstall'])) {
                if (file_exists(_PUBLIC_PATH . _PLUGIN_PATH . $plugin->getInfo()->url . DIRECTORY_SEPARATOR . $plugin->getInfo()->database['uninstall'])) {
                    require_once _PUBLIC_PATH . _PLUGIN_PATH . $plugin->getInfo()->url . DIRECTORY_SEPARATOR . $plugin->getInfo()->database['uninstall'];
                }
            }
            $plugin->delete();
        } catch (Exception $e) {
            Message::add("Error uninstalling plugin: " . $e->getMessage(), _BASE_PATH . 'admin/plugin');
        }
    }
}
