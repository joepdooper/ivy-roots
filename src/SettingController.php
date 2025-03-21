<?php

namespace Ivy;

use GUMP;

class SettingController extends Controller
{
    private Setting $setting;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();
        $this->requireAdmin();

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
        $this->requireGet();
        $this->requireLogin();
        $this->requireAdmin();

        $settings = (new Setting)->fetchAll();
        Template::view('admin/setting.latte', ['settings' => $settings]);
    }
}