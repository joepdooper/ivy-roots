<?php

namespace Ivy\Plugin\Presentation\Controller;

use Contacts\Contact;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Domain\Enum\PluginStatus;
use Ivy\Plugin\Infrastructure\Manager\PluginManager;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfo;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoFactory;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoLoader;
use Ivy\Plugin\Infrastructure\Service\PluginService;
use Ivy\Plugin\Presentation\Form\PluginForm;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Sprout\ComposerRunner;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use ReflectionException;
use Symfony\Component\Process\Process;

class PluginController extends Controller
{
    private Plugin $plugin;

    private PluginForm $pluginForm;

    private PluginManager $pluginManager;

    /**
     * @var list<array{status: string, message: string|array<string, mixed>}>
     */
    private array $responses = [];

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin;
        $this->pluginForm = new PluginForm;
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

    public function index(?string $id = null): void
    {
        $this->plugin->authorize('index');

        $installedPlugins = collect(Plugin::all())->map(function ($plugin) use (&$installedUrls) {
            if ($plugin->status !== PluginStatus::PENDING) {
                $loader = new PluginInfoLoader;
                $factory = new PluginInfoFactory;

                $data = $loader->load($plugin->url);
                $data['url'] = $plugin->url;

                $plugin->info = $factory->make($data);
                $installedUrls[$plugin->url] = true;
            }

            return $plugin;
        });

        $uninstalledPlugins = [];

        if (!$id) {
            $pluginsPath = Path::get('PLUGINS_PATH');

            if (is_dir($pluginsPath)) {

                $ignore = ['.' => true, '..' => true, '.DS_Store' => true];

                foreach (scandir($pluginsPath) as $plugin) {

                    if (isset($ignore[$plugin]) || isset($installedUrls[$plugin])) {
                        continue;
                    }

                    $infoPath = $pluginsPath . $plugin . '/info.json';

                    if (!is_file($infoPath)) {
                        continue;
                    }

                    $contents = file_get_contents($infoPath);

                    if ($contents) {
                        $info = json_decode($contents);
                    } else {
                        $info = null;
                    }

                    if ($info === null) {
                        continue;
                    }

                    $info->url = $plugin;
                    $uninstalledPlugins[] = $info;
                }
            }
        }

        $catalogPlugins = ComposerRunner::findPlugins();

        View::render('admin/plugin.latte', [
            'installed_plugins' => $installedPlugins,
            'uninstalled_plugins' => $uninstalledPlugins,
            'catalog_plugins' => $catalogPlugins,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws Exception
     */
    public function add(array $data): void
    {
        $this->plugin->authorize('install');

        try {
            $plugin = new Plugin;
            $plugin->fill($data);

            $this->pluginManager = new PluginManager($plugin);
            $this->pluginManager->install();

            $this->responses[] = [
                'status' => 'success',
                'message' => Language::translate(
                    'plugin.installed_successfully',
                    ['plugin' => $plugin->name]
                ),
            ];

        } catch (\Throwable $e) {
            $this->responses[] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

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
            $this->pluginManager = new PluginManager($plugin);
            $this->pluginManager->uninstall();

            $this->responses[] = [
                'status' => 'success',
                'message' => Language::translate(
                    'plugin.uninstalled_successfully',
                    ['plugin' => $plugin->name]
                ),
            ];

        } catch (\Throwable $e) {
            $this->responses[] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function sync(): void
    {
        $this->plugin->authorize('sync');

        if ($this->request->request->has('plugin')) {
            foreach ($this->request->request->all('plugin') as $index => $data) {

                $result = $this->pluginForm->validate($data);

                if ($result->valid) {
                    if (empty($result->data['id'])) {
                        $this->add($result->data);
                    } elseif (isset($result->data['delete'])) {
                        $this->delete($result->data['id']);
                    } else {
                        $this->update($result->data['id'], $result->data);
                    }
                } else {
                    $errors[$index] = $result->errors;
                    $old[$index] = $result->old;
                }
            }
        }

        foreach ($this->responses as $response) {
            $this->flashBag->add($response['status'], $response['message']);
        }

        $this->redirect('admin/plugin');
    }

    /**
     * @throws AuthorizationException
     */
    public function download(): void
    {
        $this->plugin->authorize('install');

        $package = $this->request->request->get('package');

        try {
//            $process = new Process(
//                [
//                    './vendor/bin/require',
//                    $package,
//                ],
//                Path::get('PROJECT_PATH')
//            );
//
//            $process->run();
//
//            file_put_contents('ivy-roots-run.log', date('c')." Process=" . $process->getOutput(). "\n", FILE_APPEND);

//            if (! $process->isSuccessful()) {
//                $this->flashBag->add(
//                    'error',
//                    'Failed to start plugin download: ' . ($process->getErrorOutput() ?: $process->getOutput())
//                );
//            }

            $data = PluginService::queuePackageMetadata($package);

            d($data);die;

            $plugin = Plugin::create([
                $data
            ])->save();

            $this->flashBag->add(
                'success',
                'Plugin download started.'
            );
        } catch (\Throwable $e) {
            $this->flashBag->add(
                'error',
                'Failed to start plugin download: ' . $e->getMessage()
            );
        }

        $this->redirect('admin/plugin');
    }
}
