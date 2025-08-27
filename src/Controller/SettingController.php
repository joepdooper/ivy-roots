<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\View\View;

class SettingController extends Controller
{
    private Setting $setting;

    public function __construct()
    {
        parent::__construct();
        $this->setting = new Setting;
    }

    public function post(): void
    {
        $this->setting->authorize('post');

        $redirect = $this->prepareData();

        $settings_data = $this->request->get('setting');

        foreach ($settings_data as $setting_data) {
            try {
                $validated = GUMP::is_valid($setting_data, [
                    'value' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/',
                    'plugin_id' => 'numeric'
                ]);
                if ($validated === true) {
                    $this->setting->save($setting_data);
                } else {
                    foreach ($validated as $string) {
                        $this->flashBag->add('error', $string);
                    }
                }
            } catch (\Exception $e) {
                $this->flashBag->add('error', $e->getMessage());
            }
        }

        $this->flashBag->add('success', 'Update successfully');
        $this->redirect($redirect);
    }

    public function index($id = null): void
    {
        $this->setting->authorize('index');
        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $settings = $this->setting->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    protected function prepareData(string $url = '', int $statusCode = 302)
    {
        $refererPath = $this->getRefererPath();
        if ($refererPath != $this->setting->getPath()){
            $segments = explode('/',$refererPath);
            if($segments[0] === 'plugin') {
                $this->setting->plugin_id = (new \Ivy\Model\Plugin)->where('url', $segments[1])->fetchOne()->getId();
            }
        }
        return $refererPath;
    }
}