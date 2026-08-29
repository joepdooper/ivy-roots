<?php

namespace Ivy\Plugin\Infrastructure\Manager;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Ivy\Plugin\Application\Contracts\PluginInterface;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Domain\Enum\PluginStatus;
use Ivy\Plugin\Domain\Exception\PluginException;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoLoader;
use Ivy\Plugin\Infrastructure\Service\PluginService;
use Ivy\Plugin\Presentation\Form\PluginForm;
use Ivy\Plugin\Presentation\Form\PluginInfoForm;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Template\Application\Asset\AssetPublisher;
use Ivy\User\Domain\Exception\AuthorizationException;

class PluginManager
{
    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public static function install(Plugin $plugin): void
    {
        $plugin->authorize('install');

        $info = PluginService::queuePackageMetadata($plugin->package);

        if (! $info) {
            throw new PluginException(message: 'no package metadata could be retreived', plugin: $plugin->url);
        }

//        $result = (new PluginForm)->validate($info);

//        if (! $result->valid) {
//            $errors = [];
//
//            foreach ($result->errors as $error) {
//                if (is_array($error)) {
//                    $errors[] = $error[0];
//                }
//            }
//
//            throw new PluginException(message: 'contains an invalid info.json file: '.implode(' ', $errors), plugin: $this->plugin->url);
//        }


//        $this->plugin->fill($result->data);

        if (isset($info['dependencies'])) {
            $missing = PluginService::getMissingDependencies($info['dependencies']);
            if (! empty($missing)) {
                throw new PluginException(message: 'is missing dependencies '.implode(', ', $missing), plugin: $this->plugin->name);
            }
        }

        $class = $plugin->interface;

        if (! class_exists($class)) {
            throw new PluginException("class {$class} not found", $plugin->name);
        }

        $instance = new $class;

        if (! $instance instanceof PluginInterface) {
            throw new PluginException("must implement {$class}", $plugin->name);
        }

        try {
            self::resolvePluginInterface($plugin)->install();
            $plugin->fill([
                'status' => PluginStatus::INSTALLED
            ])->save();
        } catch (Exception $exception) {
            throw new PluginException($exception->getMessage(), $plugin->name);
        }

//        try {
//            Capsule::connection()->transaction(function () use ($info) {
//                $this->plugin->save();
//
//                if (isset($info['settings'])) {
//                    foreach ($info['settings'] as $setting) {
//                        new Setting()->fill([
//                            ...$setting,
//                            'plugin_id' => $this->plugin->id,
//                            'is_default' => 1,
//                        ])->save();
//                    }
//                }
//
//                new AssetPublisher()->publishPlugin($this->plugin->url);
//
//                if (! empty($info['collection'])) {
//
//                    $paths = glob(PluginService::getCollectionDirectory($this->plugin->url).'[a-zA-Z0-9_-]*');
//
//                    if ($paths === false) {
//                        throw new PluginException(message: 'cannot read plugin collection directory', plugin: $this->plugin->name);
//                    }
//
//                    $subfolders = array_filter($paths, 'is_dir');
//
//                    foreach ($subfolders as $subfolder) {
//                        try {
//                            $plugin = new Plugin;
//                            $pluginManager = new PluginManager($plugin->fill([
//                                'url' => PluginService::getRelativePath($subfolder),
//                            ]));
//                            $pluginManager->install();
//                        } catch (PluginException $e) {
//                            throw new PluginException(message: 'cannot install plugin from its collection because '.$e->getMessage(), plugin: $this->plugin->name, previous: $e);
//                        }
//                    }
//                }
//            });
//        } catch (PluginException $e) {
//            $this->resolvePluginInterface()->uninstall();
//            throw new PluginException(message: 'installation failed. '.$e->getMessage(), plugin: $this->plugin->name, previous: $e);
//        }
    }

    /**
     * @throws AuthorizationException
     */
    public static function uninstall(Plugin $plugin): void
    {
        $plugin->authorize('uninstall');

//        $info = (new PluginInfoLoader)->load($plugin->url);
//
//        if (! $info) {
//            throw new PluginException(message: 'has no info.json', plugin: $plugin->url);
//        }
//
//        try {
//            Capsule::connection()->transaction(function () use ($info, $plugin) {
//                if (! empty($info['collection'])) {
//
//                    $paths = glob(PluginService::getCollectionDirectory($plugin->url).'[a-zA-Z0-9_-]*');
//
//                    if ($paths === false) {
//                        throw new PluginException(message: 'cannot read plugin collection directory', plugin: $this->plugin->name);
//                    }
//
//                    $subfolders = array_filter($paths, 'is_dir');
//
//                    foreach ($subfolders as $subfolder) {
//                        try {
//                            $plugin = Plugin::where('url', PluginService::getRelativePath($subfolder))
//                                ->where('parent_id', $plugin->id)
//                                ->first();
//                            PluginManager::uninstall($plugin);
//                        } catch (PluginException $e) {
//                            throw new PluginException(message: 'cannot uninstall plugin from its collection because '.$e->getMessage(), plugin: $this->plugin->name, previous: $e);
//                        }
//                    }
//                }
//
//                Setting::where('plugin_id', $plugin->id)->delete();
//                $plugin->delete();
//            });
//        } catch (PluginException $e) {
//            throw new PluginException(message: 'cannot uninstall plugin because '.$e->getMessage(), plugin: $plugin->name, previous: $e);
//        }

        try {
            self::resolvePluginInterface($plugin)->uninstall();
            $plugin->delete();
        } catch (Exception $exception) {
            throw new PluginException($exception->getMessage(), $plugin->name);
        }
    }

    private static function resolvePluginInterface(Plugin $plugin): PluginInterface
    {
        $class = $plugin->interface;

        if (! class_exists($class)) {
            throw new PluginException("class {$class} not found", $plugin->name);
        }

        $instance = new $class;

        if (! $instance instanceof PluginInterface) {
            throw new PluginException("must implement {$class}", $plugin->name);
        }

        return $instance;
    }
}
