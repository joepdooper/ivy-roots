<?php

namespace Ivy\Manager;

use Ivy\Controller\PluginController;
use Ivy\Form\PluginInfoForm;
use Ivy\Helper\PluginHelper;
use Ivy\Helper\PluginInfoLoader;
use Ivy\Model\Plugin;

class PluginCollectionManager
{
    public function __construct(
        private Plugin $plugin
    ) {}

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
        $paths = glob(PluginHelper::getCollectionDirectory($this->plugin->url) . '[a-zA-Z0-9_-]*');

        if ($paths === false) {
            return;
        }

        $subfolders = array_filter($paths, 'is_dir');

        foreach ($subfolders as $subfolder) {
            $relativePath = PluginHelper::getRelativePath($subfolder);

            if ($action === 'install') {
                $data = ['url' => $relativePath];

                $plugin = new Plugin();
                $pluginManager = new PluginManager($plugin->fill($data));
                $pluginManager->install();
                continue;
            }

            if ($action === 'uninstall') {
                $plugin = Plugin::where('url', $relativePath)
                    ->where('parent_id', $this->plugin->id)
                    ->first();

                if ($plugin !== null) {
                    $pluginManager = new PluginManager($plugin);
                    $pluginManager->uninstall();
                }
            }
        }
    }
}
