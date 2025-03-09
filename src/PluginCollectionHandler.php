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
        $collectionPath = Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $this->plugin->url . DIRECTORY_SEPARATOR . 'collection';
        $realCollectionPath = realpath($collectionPath);

        if (!$realCollectionPath || !str_starts_with($realCollectionPath, Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH'))) {
            return;
        }

        $subfolders = array_filter(glob($realCollectionPath . DIRECTORY_SEPARATOR . '[a-zA-Z0-9_-]*'), 'is_dir');

        foreach ($subfolders as $subfolder) {

            $infoJsonPath = $subfolder . DIRECTORY_SEPARATOR . 'info.json';
            $infoJsonContent = JsonHelper::parse($infoJsonPath);

            if (!$infoJsonContent || !isset($infoJsonContent['name'])) {
                Message::add('Invalid JSON in: ' . $infoJsonPath);
                continue;
            }

            $this->processScript($subfolder, $infoJsonContent, $action);
        }
    }

    private function processScript(string $subfolder, array $infoJsonContent, string $action): void
    {
        if (isset($infoJsonContent['database'][$action])) {
            $scriptDefinition = $infoJsonContent['database'][$action];
            $scriptPath = $subfolder . DIRECTORY_SEPARATOR . $scriptDefinition;

            if (is_dir($scriptPath)) {
                $scriptPath = rtrim($scriptPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($scriptDefinition);
            }

            if (file_exists($scriptPath) && preg_match('#^[a-zA-Z0-9_/.\-]+\.php$#', $scriptDefinition)) {
                require_once $scriptPath;
            } else {
                Message::add('Invalid script path or filename: ' . $scriptPath);
            }

            $url = $this->plugin->url . DIRECTORY_SEPARATOR . 'collection' . DIRECTORY_SEPARATOR . basename($subfolder);
            $plugin = new Plugin();
            $plugin->url = $url;
            $plugin->setInfo();
            $plugin->parent_id = $this->plugin->id;

            if ($action === 'install') {
                $plugin->insert();
            }
            if ($action === 'uninstall') {
                $plugin->where('url', $url)->where('parent_id', $this->plugin->id)->delete();
            }
        }
    }
}