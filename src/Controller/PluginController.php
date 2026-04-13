<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\PluginForm;
use Ivy\Manager\PluginManager;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
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

    public function install(mixed $data): void
    {
        $plugin = new Plugin;

        $plugin->authorize('install');
        $plugin->populate($data);

        $this->pluginManager = new PluginManager($plugin);
        $this->responses[] = $this->pluginManager->install();
    }

    public function update(Plugin|int $plugin, mixed $data): void
    {
        if(is_int($plugin)) {
            $plugin = (new Plugin)->where('id', $plugin)->fetchOne();
        }

        if($plugin && $plugin->isDirty($data)) {
            $plugin->authorize('update');
            $plugin->populate($data)->update();
            $this->flashBag->add('success', 'Plugin ' . $plugin->name . ' updated successfully.');
        }
    }

    public function uninstall(Plugin|int $plugin): void
    {
        if(is_int($plugin)) {
            $plugin = (new Plugin)->where('id', $plugin)->fetchOne();
        }

        if($plugin){
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
                if(empty($result->data['id'])){
                    $this->install($result->data);
                } elseif(isset($result->data['delete'])) {
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

    public function index(?int $id = null): void
    {
        $this->plugin->authorize('index');

        if ($id) {
            $parent_id = (new Plugin)->where('url', $id)->fetchOne()?->getId();
            $uninstalled_plugins = null;
        } else {
            $parent_id = null;
        }
        // -- Installed plugins from database
        $installed_plugins = (new Plugin)->where('parent_id', $parent_id)->fetchAll();
        // -- Uninstalled plugins from directory
        $values_to_remove_from_uninstalled_plugins = ['.', '..', '.DS_Store'];
        foreach ($installed_plugins as $plugin) {
            $plugin->setInfo();
            $values_to_remove_from_uninstalled_plugins[] = $plugin->url;
        }
        $uninstalled_plugins = [];
        if (! $id && is_dir(Path::get('PLUGINS_PATH'))) {
            $uninstalled_plugins = array_filter(scandir(Path::get('PLUGINS_PATH')), function ($plugin) use ($values_to_remove_from_uninstalled_plugins) {
                return ! in_array($plugin, $values_to_remove_from_uninstalled_plugins);
            });
            $uninstalled_plugins_info = [];
            foreach ($uninstalled_plugins as $key => $plugin) {
                $uninstalled_plugins_info[$key] = json_decode((string) file_get_contents(Path::get('PLUGINS_PATH').$plugin.'/info.json'));
                $uninstalled_plugins_info[$key]->url = $plugin;
            }
            $uninstalled_plugins = $uninstalled_plugins_info;
        }
        View::set('admin/plugin.latte', ['installed_plugins' => $installed_plugins, 'uninstalled_plugins' => $uninstalled_plugins]);
    }
}
