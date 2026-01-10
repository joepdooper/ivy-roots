<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Form\SettingForm;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\View\View;

class SettingController extends Controller
{
    private Setting $setting;

    public function __construct()
    {
        parent::__construct();
        $this->setting = new Setting;
    }

    public function index($id = null): void
    {
        $this->setting->authorize('index');

        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $settings = $this->setting->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    public function post(): void
    {
        $this->setting->authorize('post');

        $redirect = $this->resolveRefererContext();

        foreach ($this->request->get('setting') as $data) {

            if (empty($data['name'])) continue;

            $result = (new SettingForm)->validate($data);

            if (!$result->valid) {
                $this->flashBag->set('errors', $result->errors);
                $this->flashBag->set('old', $result->old);
                $this->redirect($redirect);
            } else {
                $info = !empty($data['id'])
                    ? (new Setting)->where('id', $data['id'])->fetchOne()
                    : new Setting();

                if (isset($data['delete']) && !empty($data['id'])) {
                    $info?->delete();
                } else {
                    $info->populate($data)->save();
                }
            }
        }

        $this->flashBag->add('success', 'Settings updated successfully.');
        $this->redirect($redirect);
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