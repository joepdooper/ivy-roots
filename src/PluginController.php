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

        $this->pluginService = new PluginService();

        $plugins_data = $this->request->input('plugin') ?? '';

        foreach ($plugins_data as $plugin_data) {

            $this->plugin = new Plugin();
            $this->plugin->populate($plugin_data);

            if (!$this->plugin->hasId()) {
                $this->pluginService->install($this->plugin);
            } else {
                $this->plugin->where('id', $this->plugin->getId())->fetchOne();
                if (isset($plugin_data['delete'])) {
                    $this->pluginService->uninstall($this->plugin);
                } else {
                    $this->plugin->update();
                }
            }

        }

        Message::add('Update successfully', _BASE_PATH . 'admin/plugin');
    }

}
