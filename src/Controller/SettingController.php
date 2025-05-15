<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\View\LatteView;

class SettingController extends Controller
{
    private Setting $setting;

    public function post(): void
    {
        $this->authorize('post', Setting::class);

        $settings_data = $this->request->get('setting');

        foreach ($settings_data as $setting_data) {
            try {
                $validated = GUMP::is_valid($setting_data, [
                    'value' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/'
                ]);
                if ($validated === true) {
                    $this->setting = new Setting;
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
        $this->redirect('admin/setting');
    }

    public function index(): void
    {
        $this->authorize('index', Setting::class);

        $settings = (new Setting)->where('plugin_id', null)->fetchAll();
        LatteView::set('admin/setting.latte', ['settings' => $settings]);
    }
}