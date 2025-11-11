<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\View\View;

class InfoController extends SettingController
{
    private Info $info;

    public function __construct()
    {
        parent::__construct();
        $this->info = new Info;
    }

    public function post(): void
    {
        $this->info->authorize('post');

        $redirect = $this->resolveRefererContext();

        foreach ($this->request->get('info') as $data) {

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

                $info = !empty($data['id'])
                    ? (new Info)->where('id', $data['id'])->fetchOne()
                    : new Info();

                if (isset($data['delete']) && !empty($data['id'])) {
                    $info?->delete();
                } else {
                    $info->populate($data)->save();
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
        $this->info->authorize('index');
        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $infos = $this->info->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/info.latte', ['infos' => $infos]);
    }
}