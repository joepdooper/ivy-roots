<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Manager\PluginManager;
use Ivy\Model\Plugin;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Core\Path;
use Ivy\View\View;

class PluginController extends Controller
{
    private Plugin $plugin;
    private PluginManager $pluginManager;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin;
    }

    public function before(): void
    {
        if (User::getAuth()->isLoggedIn()) {
            if (!User::canEditAsSuperAdmin()) {
                $this->redirect();
            }
        } else {
            $this->redirect('user/login');
        }
    }

    public function post(): void
    {
        $this->plugin->policy('post');

        $plugins_data = $this->request->get('plugin') ?? '';
        $responses = [];

        foreach ($plugins_data as $plugin_data) {

            $this->plugin = (new Plugin)->populate($plugin_data);

            if (!$this->plugin->hasId()) {
                $this->pluginManager = new PluginManager($this->plugin);
                $responses[] = $this->pluginManager->install();
            } else {
                $this->plugin->where('id', $plugin_data['id'])->fetchOne()->populate($plugin_data);
                if (isset($plugin_data['delete'])) {
                    $this->pluginManager = new PluginManager($this->plugin);
                    $responses[] = $this->pluginManager->uninstall();
                } else {
                    $this->plugin->update();
                }
            }

        }

        foreach ($responses as $response){
            $this->flashBag->add($response['status'], $response['message']);
        }

        $this->flashBag->add('success', 'Update successfully');
        $this->redirect('admin/plugin');
    }

    public function index($id = null): void
    {
        $this->plugin->policy('index');

        if($id) {
            $parent_id = (new Plugin)->where('url', $id)->fetchOne()->getId();
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
        if(!$id) {
            $uninstalled_plugins = array_filter(scandir(Path::get('PLUGINS_PATH')), function ($plugin) use ($values_to_remove_from_uninstalled_plugins) {
                return !in_array($plugin, $values_to_remove_from_uninstalled_plugins);
            });
            $uninstalled_plugins_info = [];
            foreach ($uninstalled_plugins as $key => $plugin) {
                $uninstalled_plugins_info[$key] = json_decode(file_get_contents(Path::get('PLUGINS_PATH') . $plugin . '/info.json'));
                $uninstalled_plugins_info[$key]->url = $plugin;
            }
            $uninstalled_plugins = $uninstalled_plugins_info;
        }
        View::set('admin/plugin.latte', ['installed_plugins' => $installed_plugins, 'uninstalled_plugins' => $uninstalled_plugins]);
    }

}
