<?php

namespace Ivy;

class PluginController extends Controller
{
    private Plugin $plugin;
    private PluginService $pluginService;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();
        $this->requireAdmin();

        $plugins_data = $this->request->get('plugin') ?? '';
        $responses = [];

        foreach ($plugins_data as $plugin_data) {

            $this->plugin = (new Plugin)->populate($plugin_data);

            if (!$this->plugin->hasId()) {
                $this->pluginService = new PluginService($this->plugin);
                $responses[] = $this->pluginService->install();
            } else {
                $this->plugin->where('id', $plugin_data['id'])->fetchOne()->populate($plugin_data);
                if (isset($plugin_data['delete'])) {
                    $this->pluginService = new PluginService($this->plugin);
                    $responses[] = $this->pluginService->uninstall();
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
        $this->requireGet();
        $this->requireLogin();
        $this->requireAdmin();

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
            $uninstalled_plugins = array_filter(scandir(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH')), function ($plugin) use ($values_to_remove_from_uninstalled_plugins) {
                return !in_array($plugin, $values_to_remove_from_uninstalled_plugins);
            });
            $uninstalled_plugins_info = [];
            foreach ($uninstalled_plugins as $key => $plugin) {
                $uninstalled_plugins_info[$key] = json_decode(file_get_contents(Path::get('PUBLIC_PATH') . Path::get('PLUGIN_PATH') . $plugin . '/info.json'));
                $uninstalled_plugins_info[$key]->url = $plugin;
            }
            $uninstalled_plugins = $uninstalled_plugins_info;
        }
        Template::view('admin/plugin.latte', ['installed_plugins' => $installed_plugins, 'uninstalled_plugins' => $uninstalled_plugins]);
    }

}
