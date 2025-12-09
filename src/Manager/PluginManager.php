<?php

namespace Ivy\Manager;

use Exception;
use Ivy\Helper\PluginHelper;
use Ivy\Core\Language;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Core\Path;

class PluginManager
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function install(): array
    {
        $this->plugin->authorize('install');
        $this->plugin->setInfo();

        try {
            if (!empty($missing = PluginHelper::getMissingDependencies($this->plugin->getInfo()->getDependencies()))) {
                $count = count($missing);
                $message = "This plugin has " . $count . ($count > 1 ? " dependencies" : " dependency") . ". Please install the " . ($count > 1 ? "plugins" : "plugin") . " " . implode(", ", $missing);
                return ['status' => 'warning', 'message' => $message];
            }

            if (isset($this->plugin->getInfo()->getDatabase()['install']) && !empty($this->plugin->getInfo()->getDatabase()['install']) && !empty($this->plugin->getInfo()->getUrl())) {
                $installPath = Path::get('PLUGINS_PATH') . $this->plugin->getInfo()->getUrl() . DIRECTORY_SEPARATOR . $this->plugin->getInfo()->getDatabase()['install'];
                if (file_exists($installPath)) {
                    require_once $installPath;
                }
            }

            $this->plugin->insert();

            if ($this->plugin->getInfo()->hasSettings()) {
                foreach($this->plugin->getInfo()->getSettings() as $setting){
                    $data = array_merge($setting, [
                        'plugin_id' => $this->plugin->id,
                        'is_default' => 1
                    ]);
                    (new Setting)->populate($data)->insert();
                }
            }

            if ($this->plugin->getInfo()->hasCollection()) {
                (new PluginCollectionManager($this->plugin))->install();
            }

            return ['status' => 'success', 'message' => Language::translate('plugin.installed_successfully', ['plugin' => $this->plugin->name])];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error installing plugin: ' . $e->getMessage()];
        }
    }

    public function uninstall(): array
    {
        $this->plugin->authorize('uninstall');
        $this->plugin->setInfo();

        try {
            if ($this->plugin->getInfo()->getCollection()) {
                (new PluginCollectionManager($this->plugin))->uninstall();
            }

            if ($this->plugin->getInfo()->hasSettings()) {
                $settings = (new Setting)->where('plugin_id', $this->plugin->id)->deleteAll();
            }

            if (isset($this->plugin->getInfo()->getDatabase()['uninstall']) && !empty($this->plugin->getInfo()->getDatabase()['uninstall'])) {
                $uninstallPath = Path::get('PLUGINS_PATH') . $this->plugin->getInfo()->getUrl() . DIRECTORY_SEPARATOR . $this->plugin->getInfo()->getDatabase()['uninstall'];
                if (file_exists($uninstallPath)) {
                    require_once $uninstallPath;
                }
            }

            $this->plugin->delete();
            return ['status' => 'success', 'message' => Language::translate('plugin.uninstalled_successfully', ['plugin' => $this->plugin->name])];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error deleting plugin: ' . $e->getMessage()];
        }
    }
}

