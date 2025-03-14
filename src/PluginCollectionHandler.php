<?php

namespace Ivy;

class PluginCollectionHandler
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function install(): void
    {
        $this->processCollection('install');
    }

    public function uninstall(): void
    {
        $this->processCollection('uninstall');
    }

    private function processCollection(string $action): void
    {
        $subfolders = array_filter(glob(PluginHelper::getCollectionDirectory($this->plugin->url) . '[a-zA-Z0-9_-]*'), 'is_dir');

        foreach ($subfolders as $subfolder) {
            $relativePath = str_replace(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'), '', $subfolder);
            $infoJsonContent = PluginHelper::parseJson($relativePath . DIRECTORY_SEPARATOR . 'info.json');
            $this->processScript($relativePath, $infoJsonContent, $action);
        }
    }

    private function processScript(string $subfolder, array $infoJsonContent, string $action): void
    {
        if (isset($infoJsonContent['database'][$action])) {
            if (preg_match('#^[a-zA-Z0-9_/.\-]+\.php$#', basename($infoJsonContent['database'][$action]))) {
                require_once PluginHelper::getRealPath(PluginHelper::getCollectionDirectory($this->plugin->url) . basename($subfolder) . DIRECTORY_SEPARATOR . $infoJsonContent['database'][$action]);
            }

            $plugin = new Plugin();
            $plugin->url = str_replace(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'),'', PluginHelper::getCollectionDirectory($this->plugin->url) . basename($subfolder));
            $plugin->setInfo();
            $plugin->parent_id = $this->plugin->id;

            if ($action === 'install') {
                $plugin->insert();
            }
            if ($action === 'uninstall') {
                $plugin->where('url', $plugin->url)->where('parent_id', $this->plugin->id)->delete();
            }
        }
    }
}