<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Factory\PluginInfoFactory;
use Ivy\Form\PluginForm;
use Ivy\Form\PluginInfoForm;
use Ivy\Helper\PluginInfoLoader;
use Ivy\Manager\PluginManager;
use Ivy\Model\Plugin;
use Ivy\Model\User;
use Ivy\View\View;

class PluginController extends Controller
{
    private Plugin $plugin;
    private PluginForm $pluginForm;
    private PluginManager $pluginManager;

    private array $responses = [];

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin;
        $this->pluginForm = new PluginForm;
    }

    public function before(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            if (! User::canEditAsSuperAdmin()) {
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

        $loader = new PluginInfoLoader();
        $factory = new PluginInfoFactory();

        $installedPlugins = Plugin::all()->map(function ($plugin) use ($loader, $factory, &$installedUrls) {
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

        View::set('admin/plugin.latte', [
            'installed_plugins' => $installedPlugins,
            'uninstalled_plugins' => $uninstalledPlugins,
        ]);
    }

    public function add(mixed $data): void
    {
        $this->plugin->authorize('install');

        $plugin = new Plugin;

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