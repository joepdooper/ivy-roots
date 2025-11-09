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

        $redirect = $this->resolveRefererContext();

        foreach ($this->request->get('setting') ?? [] as $data) {
            try {
                $validated = \GUMP::is_valid($data, [
                    'name' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/',
                    'value' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/',
                    'plugin_id' => 'numeric'
                ]);

                if ($validated !== true) {
                    foreach ($validated as $msg) $this->flashBag->add('error', $msg);
                    continue;
                }

                if (empty($data['name'])) continue;

                $setting = !empty($data['id'])
                    ? (new Setting())->where('id', $data['id'])->fetchOne()
                    : new Setting();

                if (isset($data['delete']) && !empty($data['id'])) {
                    $setting?->delete();
                } else {
                    $setting->populate($data)->save();
                }

            } catch (\Exception $e) {
                $this->flashBag->add('error', $e->getMessage());
            }
        }

        $this->flashBag->add('success', 'Settings updated successfully.');
        $this->redirect($redirect);
    }

    public function index($id = null): void
    {
        $this->setting->authorize('index');
        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $settings = $this->setting->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    protected function resolveRefererContext(string $url = '', int $statusCode = 302)
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