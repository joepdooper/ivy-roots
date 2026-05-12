<?php

namespace Ivy\Plugin\Presentation\Controller;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Path;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoFactory;
use Ivy\Plugin\Presentation\Form\PluginForm;
use Ivy\Plugin\Infrastructure\Metadata\PluginInfoLoader;
use Ivy\Plugin\Infrastructure\Manager\PluginManager;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use JetBrains\PhpStorm\NoReturn;
use ReflectionException;

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
        $this->plugin = new Plugin();
        $this->pluginForm = new PluginForm();
    }

    /**
     * @throws ReflectionException
     * @throws AuthorizationException
     * @throws BindingResolutionException
     */
    public function before(): void
    {
        if ($this->authService->isLoggedIn()) {
            if($this->plugin->policy('index')) {
                $this->redirect();
            }
        } else {
            $this->redirect('user/login');
        }
    }

    public function index(?string $id = null): void
    {
        $this->plugin->authorize('index');

        $parentId = $id
            ? Plugin::where('url', $id)->value('id')
            : null;

        $installedPlugins = Plugin::all()->map(function ($plugin) use (&$installedUrls) {
            $loader = new PluginInfoLoader();
            $factory = new PluginInfoFactory();

            $data = $loader->load($plugin->url);
            $data['url'] = $plugin->url;

            $plugin->info = $factory->make($data);

            $installedUrls[$plugin->url] = true;
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

                    $info = json_decode(file_get_contents($infoPath));

                    if ($info === null) {
                        continue;
                    }

                    $info->url = $plugin;
                    $uninstalledPlugins[] = $info;
                }
            }
        }

        View::render('admin/plugin.latte', [
            'installed_plugins' => $installedPlugins,
            'uninstalled_plugins' => $uninstalledPlugins,
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

        $plugin = new Plugin();

        $this->pluginManager = new PluginManager($plugin->fill($data));
        $this->responses[] = $this->pluginManager->install();
    }

    public function update(Plugin|int $plugin, mixed $data): void
    {

        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        $plugin->fill($data);

        if (! $plugin->isDirty()) {
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

        if (! $plugin) {
            return;
        }

        $plugin->authorize('uninstall');

        $this->pluginManager = new PluginManager($plugin);
        $this->responses[] = $this->pluginManager->uninstall();
    }

    #[NoReturn]
    public function sync(): void
    {
        $this->plugin->authorize('sync');

        if($this->request->request->has('plugin')){
            foreach ($this->request->get('plugin') as $index => $data) {

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
}
