<?php

namespace Ivy\Plugin\Infrastructure\Manager;

use Exception;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Infrastructure\Service\PluginService;

readonly class PluginCollectionManager
{
    public function __construct(
        private Plugin $plugin
    ) {}

    /**
     * @throws Exception
     */
    public function install(): void
    {
        $this->processCollection('install');
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        $this->processCollection('uninstall');
    }

    /**
     * @throws Exception
     */
    private function processCollection(string $action): void
    {
        $paths = glob(PluginService::getCollectionDirectory($this->plugin->url) . '[a-zA-Z0-9_-]*');

        if ($paths === false) {
            return;
        }

        $subfolders = array_filter($paths, 'is_dir');

        foreach ($subfolders as $subfolder) {
            $relativePath = PluginService::getRelativePath($subfolder);

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

                if ($plugin) {
                    $pluginManager = new PluginManager($plugin);
                    $pluginManager->uninstall();
                }
            }
        }
    }
}
