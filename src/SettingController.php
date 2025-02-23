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

        $settings_data = $this->request->input('setting') ?? '';

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
                        Message::add($string);
                    }
                }
            } catch (\Exception $e) {
                Message::add($e->getMessage());
            }
        }

        Message::add('Update successfully', _BASE_PATH . 'admin/setting');
    }
}