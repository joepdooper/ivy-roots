<?php

namespace Ivy\Plugin\Infrastructure\Manager;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\Plugin\Domain\Exception\PluginException;
use Ivy\Plugin\Infrastructure\Service\PluginService;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Template\Application\Asset\AssetPublisher;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Application\Contracts\PluginInterface;
use Ivy\Shared\Core\Language;
use Ivy\Plugin\Presentation\Form\PluginInfoForm;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoLoader;

class PluginManager
{
    public function __construct(
        private Plugin $plugin
    ) {}

    /**
     * @throws Exception
     */
    private function resolvePluginInterface(): PluginInterface
    {
        $class = $this->plugin->interface;

        if (!class_exists($class)) {
            throw new PluginException("class {$class} not found", $this->plugin->name);
        }

        $instance = new $class();

        if (!$instance instanceof PluginInterface) {
            throw new PluginException("must implement {$class}", $this->plugin->name);
        }

        return $instance;
    }

    public function install():void
    {
        $this->plugin->authorize('install');

        $info = (new PluginInfoLoader)->load($this->plugin->url);

        if (!$info) {
            throw new PluginException(message: 'contains no info.json', plugin: $this->plugin->url);
        }

        $result = (new PluginInfoForm)->validate($info);

        if (!$result->valid) {
            $errors = [];

            foreach ($result->errors as $error) {
                if (is_array($error)) {
                    $errors[] = $error[0];
                }
            }

            throw new PluginException(message: 'contains an invalid info.json file: ' . implode(' ', $errors), plugin: $this->plugin->url);
        }

        $this->plugin->fill($result->data);

        if (isset($info['dependencies'])) {
            $missing = PluginService::getMissingDependencies($info['dependencies']);
            if (!empty($missing)) {
                throw new PluginException(message: 'is missing dependencies ' . implode(', ', $missing), plugin: $this->plugin->name);
            }
        }

        $this->resolvePluginInterface()->install();

        try {
            Capsule::connection()->transaction(function () use ($info) {
                $this->plugin->save();

                if (isset($info['settings'])) {
                    foreach ($info['settings'] as $setting) {
                        new Setting()->fill([
                            ...$setting,
                            'plugin_id' => $this->plugin->id,
                            'is_default' => 1,
                        ])->save();
                    }
                }

                new AssetPublisher()->publishPlugin($this->plugin->url);

                if (!empty($info['collection'])) {

                    $paths = glob(PluginService::getCollectionDirectory($this->plugin->url) . '[a-zA-Z0-9_-]*');

                    if ($paths === false) {
                        throw new PluginException(message: 'cannot read plugin collection directory', plugin: $this->plugin->name);
                    }

                    $subfolders = array_filter($paths, 'is_dir');

                    foreach ($subfolders as $subfolder) {
                        try {
                            $plugin = new Plugin();
                            $pluginManager = new PluginManager($plugin->fill([
                                'url' => PluginService::getRelativePath($subfolder)
                            ]));
                            $pluginManager->install();
                        } catch (PluginException $e) {
                            throw new PluginException(message: 'cannot install plugin from its collection because ' . $e->getMessage(), plugin: $this->plugin->name, previous: $e);
                        }
                    }
                }
            });
        } catch (PluginException $e) {
            $this->resolvePluginInterface()->uninstall();
            throw new PluginException(message: 'installation failed. ' . $e->getMessage(), plugin: $this->plugin->name, previous: $e);
        }
    }

    public function uninstall(): void
    {
        $this->plugin->authorize('uninstall');

        $info = (new PluginInfoLoader)->load($this->plugin->url);

        if (!$info) {
            throw new PluginException(message: 'has no info.json', plugin: $this->plugin->url);
        }

        try {
            Capsule::connection()->transaction(function () use ($info) {
                if (!empty($info['collection'])) {

                    $paths = glob(PluginService::getCollectionDirectory($this->plugin->url) . '[a-zA-Z0-9_-]*');

                    if ($paths === false) {
                        throw new PluginException(message: 'cannot read plugin collection directory', plugin: $this->plugin->name);
                    }

                    $subfolders = array_filter($paths, 'is_dir');

                    foreach ($subfolders as $subfolder) {
                        try {
                            $plugin = Plugin::where('url', PluginService::getRelativePath($subfolder))
                                ->where('parent_id', $this->plugin->id)
                                ->first();
                            $pluginManager = new PluginManager($plugin);
                            $pluginManager->uninstall();
                        } catch (PluginException $e) {
                            throw new PluginException(message: 'cannot uninstall plugin from its collection because ' . $e->getMessage(), plugin: $this->plugin->name, previous: $e);
                        }
                    }
                }

                Setting::where('plugin_id', $this->plugin->id)->delete();
                $this->plugin->delete();
            });
        } catch (PluginException $e) {
            throw new PluginException(message: 'cannot uninstall plugin because ' . $e->getMessage(), plugin: $this->plugin->name, previous: $e);
        }

        $this->resolvePluginInterface()->uninstall();
    }
}
