<?php

namespace Ivy\Plugin\Infrastructure\Manager;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
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
            throw new Exception("Plugin class not found: {$class}");
        }

        $instance = new $class();

        if (!$instance instanceof PluginInterface) {
            throw new Exception("Plugin must implement PluginInterface: {$class}");
        }

        return $instance;
    }

    private function getPluginInfo(): array
    {
        $info = (new PluginInfoLoader)->load($this->plugin->url);

        if (!$info) {
            throw new Exception('info.json not found');
        }

        $result = (new PluginInfoForm)->validate($info);

        if (!$result->valid) {
            $errors = [];

            foreach ($result->errors as $error) {
                if (is_array($error)) {
                    $errors[] = $error[0];
                }
            }

            throw new Exception(
                'Invalid info.json: ' . implode(' ', $errors)
            );
        }

        return $result->data;
    }

    /**
     * @return array{
     *     status: string,
     *     message: string|array<string, mixed>
     * }
     * @throws Exception
     */
    public function install(): array
    {
        $this->plugin->authorize('install');

        try {
            $info = $this->getPluginInfo();
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        $this->plugin->fill($info);

        if (isset($info['dependencies'])) {
            $missing = PluginService::getMissingDependencies($info['dependencies']);
            if (!empty($missing)) {
                return [
                    'status' => 'warning',
                    'message' => 'Missing dependencies: ' . implode(', ', $missing),
                ];
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
                    new PluginCollectionManager($this->plugin)->install();
                }
            });
        } catch (Exception $e) {
            $this->resolvePluginInterface()->uninstall();
            return [
                'status' => 'error',
                'message' => 'Error installing plugin: ' . $e->getMessage()
            ];
        } catch (\Throwable $e) {
        }

        return [
            'status' => 'success',
            'message' => Language::translate(
                'plugin.installed_successfully',
                ['plugin' => $this->plugin->name]
            )
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     message: string|array<string, mixed>
     * }
     * @throws Exception
     */
    public function uninstall(): array
    {
        $this->plugin->authorize('uninstall');

        $info = (new PluginInfoLoader)->load($this->plugin->url);

        try {
            Capsule::connection()->transaction(function () use ($info) {
                if (!empty($info['collection'])) {
                    new PluginCollectionManager($this->plugin)->uninstall();
                }

                Setting::where('plugin_id', $this->plugin->id)->delete();
                $this->plugin->delete();
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error uninstalling plugin: ' . $e->getMessage()
            ];
        } catch (\Throwable $e) {
        }

        $this->resolvePluginInterface()->uninstall();

        return [
            'status' => 'success',
            'message' => Language::translate(
                'plugin.uninstalled_successfully',
                ['plugin' => $this->plugin->name]
            )
        ];
    }
}
