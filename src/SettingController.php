<?php

namespace Ivy;

use Exception;

class SettingController extends Controller
{
    protected Setting $setting;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();

        $settings_data = $this->request->input('setting') ?? '';

        foreach ($settings_data as $setting_data) {
            (new Setting)->save($setting_data);
        }

        Message::add('Update successfully', _BASE_PATH . 'admin/setting');
    }
}