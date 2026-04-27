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
        $subfolders = array_filter(glob(PluginHelper::getCollectionDirectory($this->plugin->url).'[a-zA-Z0-9_-]*'), 'is_dir');

        foreach ($subfolders as $subfolder) {
            $relativePath = PluginHelper::getRelativePath($subfolder);
            switch ($action) {
                case 'install':
                    $data = ['url' => $relativePath];
                    $plugin = new Plugin;
                    $pluginManager = new PluginManager($plugin->fill($data));
                    $pluginManager->install();
                    break;
                case 'uninstall':
                    $plugin = Plugin::where('url', $relativePath)->where('parent_id', $this->plugin->id)->first();
                    if($plugin){
                        $pluginManager = new PluginManager($plugin);
                        $pluginManager->uninstall();
                    }
                    break;
                default:
                    // no-op
                    break;
            }
        }
    }
}
