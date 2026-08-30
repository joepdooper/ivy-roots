<?php

namespace Ivy\Plugin\Presentation\Controller;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Domain\Enum\PluginStatus;
use Ivy\Plugin\Domain\Exception\PluginException;
use Ivy\Plugin\Infrastructure\Manager\PluginManager;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoFactory;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoLoader;
use Ivy\Plugin\Infrastructure\Service\PluginService;
use Ivy\Plugin\Presentation\Form\PluginForm;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Language;
use Ivy\Sprout\BackgroundProcess;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use ReflectionException;

class PluginController extends Controller
{
    private Plugin $plugin;

    private BackgroundProcess $backgroundProcess;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin;
        $this->backgroundProcess = new BackgroundProcess;
    }

    /**
     * @throws ReflectionException
     * @throws AuthorizationException
     * @throws BindingResolutionException
     */
    public function before(): void
    {
        if ($this->authService->isLoggedIn()) {
            if ($this->plugin->policy('index')) {
                $this->redirect();
            }
        } else {
            $this->redirect('user/login');
        }
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function index(): void
    {
        $this->plugin->authorize('index');

        $plugins = Plugin::all()
            ->map(function ($plugin) {
                if ($plugin->status === PluginStatus::DOWNLOADING) {
                    if (PluginService::exists($plugin->url.DIRECTORY_SEPARATOR.'composer.json')) {
                        $plugin->status = PluginStatus::DOWNLOADED;
                    }
                }
                if ($plugin->status === PluginStatus::DOWNLOADED) {
                    if (PluginService::exists($plugin->url.DIRECTORY_SEPARATOR.'composer.json')) {
                        try {
                            PluginManager::install($plugin);
                        } catch (PluginException $e) {
                            $this->flashBag->add(
                                'error',
                                $e->getMessage()
                            );
                        }
                    }
                }

                return $plugin;
            })
            ->keyBy('package');

        $catalogPlugins = PluginService::getPluginCatalog();

        $catalogPlugins = collect($catalogPlugins)
            ->reject(function (array $catalogPlugin) use ($plugins) {
                return $plugins->has($catalogPlugin['package']);
            })
            ->values()
            ->all();

        View::render('admin/plugin.latte', [
            'installed_plugins' => $plugins,
            'catalog_plugins' => $catalogPlugins,
        ]);
    }

//    public function sync(): void
//    {
//        $this->plugin->authorize('sync');
//
//        if ($this->request->request->has('plugin')) {
//            foreach ($this->request->request->all('plugin') as $index => $data) {
//
//                $result = $this->pluginForm->validate($data);
//
//                if ($result->valid) {
//                    if (empty($result->data['id'])) {
//                        $this->add($result->data);
//                    } elseif (isset($result->data['delete'])) {
//                        $this->delete($result->data['id']);
//                    } else {
//                        $this->update($result->data['id'], $result->data);
//                    }
//                } else {
//                    $errors[$index] = $result->errors;
//                    $old[$index] = $result->old;
//                }
//            }
//        }
//
//        foreach ($this->responses as $response) {
//            $this->flashBag->add($response['status'], $response['message']);
//        }
//
//        $this->redirect('admin/plugin');
//    }

    /**
     * @throws AuthorizationException
     */
    public function add(): void
    {
        $this->plugin->authorize('install');

        $package = $this->request->request->get('package');

        try {
            $plugin = Plugin::firstOrCreate(
                ['package' => $package],
                [
                    ...PluginService::queuePackageMetadata($package),
                    'status' => PluginStatus::DOWNLOADING,
                ]
            );

                $this->backgroundProcess->require($package);

            $this->flashBag->add(
                'success',
                Language::translate('plugin.added_successfully', ['plugin' => $plugin->name])
            );
        } catch (\Throwable $e) {
            $this->flashBag->add(
                'error',
                'Failed to start plugin download: ' . $e->getMessage()
            );
        }

        $this->redirect('admin/plugin');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Plugin|int $plugin, mixed $data): void
    {

        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        $plugin->fill($data);

        if (!$plugin->isDirty()) {
            return;
        }

        $plugin->authorize('update');

        $plugin->save();

        $this->flashBag->add(
            'success',
            'Plugin ' . $plugin->name . ' updated successfully.'
        );
    }

    /**
     * @throws Exception
     */
    public function delete(Plugin|int $plugin): void
    {
        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        $plugin->authorize('uninstall');

        try {
            $this->backgroundProcess->remove($plugin->package);

            PluginManager::uninstall($plugin);

            $this->flashBag->add(
                'success',
                Language::translate('plugin.uninstalled_successfully', [
                    'plugin' => $plugin->name
                ])
            );

        } catch (\Throwable $e) {
            $this->flashBag->add(
                'error',
                $e->getMessage(),
            );
        }

        $this->redirect('admin/plugin');
    }
}
