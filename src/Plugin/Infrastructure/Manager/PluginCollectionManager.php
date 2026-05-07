<?php

namespace Ivy\Infrastructure\Manager;

use Ivy\Domain\Model\PluginModel;
use Ivy\Infrastructure\Helper\PluginHelper;

readonly class PluginCollectionManager
{
    public function __construct(
        private PluginModel $plugin
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

                $plugin = new PluginModel();
                $pluginManager = new PluginManager($plugin->fill($data));
                $pluginManager->install();
                continue;
            }

            if ($action === 'uninstall') {
                $plugin = PluginModel::where('url', $relativePath)
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
