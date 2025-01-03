<?php

namespace Ivy;

class PluginController extends Controller
{
    protected Plugin $plugin;
    protected PluginService $pluginService;

    public function post(Request $request = null): void
    {
        $request = $request ?? new Request();
        $this->pluginService = new PluginService();

        if ($request->isMethod('POST') && User::isLoggedIn()) {

            $plugins = $request->input('plugin') ?? '';

            foreach ($plugins as $plugin_data) {

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
        }
        Message::add('Update successfully', _BASE_PATH . 'admin/plugin');
    }

}
