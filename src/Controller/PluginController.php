<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\PluginForm;
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

        $parent_id = null;

        if ($id) {
            $parent_id = Plugin::where('url', $id)->value('id');
        }

        $installed_plugins = Plugin::all();

        $values_to_remove = ['.', '..', '.DS_Store'];

        foreach ($installed_plugins as $plugin) {
            $plugin->setInfo();
            $values_to_remove[] = $plugin->url;
        }

        $uninstalled_plugins = [];

        if (! $id && is_dir(Path::get('PLUGINS_PATH'))) {

            $uninstalled_plugins = array_filter(
                scandir(Path::get('PLUGINS_PATH')),
                fn ($plugin) => ! in_array($plugin, $values_to_remove)
            );

            $uninstalled_plugins_info = [];

            foreach ($uninstalled_plugins as $key => $plugin) {
                $info = json_decode(
                    file_get_contents(Path::get('PLUGINS_PATH') . $plugin . '/info.json')
                );

                $info->url = $plugin;
                $uninstalled_plugins_info[$key] = $info;
            }

            $uninstalled_plugins = $uninstalled_plugins_info;
        }
        
        View::set('admin/plugin.latte', [
            'installed_plugins' => $installed_plugins,
            'uninstalled_plugins' => $uninstalled_plugins
        ]);
    }

    public function install(mixed $data): void
    {
        $plugin = new Plugin;

        $plugin->authorize('install');
        $plugin->fill($data);

        $this->pluginManager = new PluginManager($plugin);
        $this->responses[] = $this->pluginManager->install();
    }

    public function update(Plugin|int $plugin, mixed $data): void
    {
        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        if ($plugin && $plugin->isDirty($data)) {
            $plugin->authorize('update');
            $plugin->fill($data)->save();

            $this->flashBag->add(
                'success',
                'Plugin ' . $plugin->name . ' updated successfully.'
            );
        }
    }

    public function uninstall(Plugin|int $plugin): void
    {
        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        if ($plugin) {
            $plugin->authorize('uninstall');

            $this->pluginManager = new PluginManager($plugin);
            $this->responses[] = $this->pluginManager->uninstall();
        }
    }

    public function sync(): void
    {
        $this->plugin->authorize('sync');

        foreach ($this->request->get('plugin') as $data) {

            $result = $this->pluginForm->validate($data);

            if ($result->valid) {

                if (empty($result->data['id'])) {
                    $this->install($result->data);

                } elseif (isset($result->data['delete'])) {
                    $this->uninstall($result->data['id']);

                } else {
                    $this->update($result->data['id'], $result->data);
                }

            } else {
                $errors[$index] = $result->errors;
                $old[$index] = $result->old;
            }
        }

        foreach ($this->responses as $response) {
            $this->flashBag->add($response['status'], $response['message']);
        }

        $this->redirect('admin/plugin');
    }
}