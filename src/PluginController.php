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

        $plugins_data = $this->request->get('plugin') ?? '';


        foreach ($plugins_data as $plugin_data) {

            $this->plugin = (new Plugin)->populate($plugin_data);

            if (!$this->plugin->hasId()) {
                $this->pluginService = new PluginService($this->plugin);
                $this->pluginService->install();
            } else {
                $this->plugin->where('id', $plugin_data['id'])->fetchOne()->populate($plugin_data);
                if (isset($plugin_data['delete'])) {
                    $this->pluginService = new PluginService($this->plugin);
                    $this->pluginService->uninstall();
                } else {
                    $this->plugin->update();
                }
            }

        }

        Message::add('Update successfully', Path::get('BASE_PATH') . 'admin/plugin');
    }

}
