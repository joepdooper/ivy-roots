<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
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

        $redirect = $this->prepareData();

        $infos_data = $this->request->get('info');

        foreach ($infos_data as $info_data) {
            try {
                $validated = GUMP::is_valid($info_data, [
                    'value' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/',
                    'plugin_id' => 'numeric'
                ]);
                if ($validated === true) {
                    $this->info->save($info_data);
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
        $this->info->authorize('index');
        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $infos = $this->info->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/info.latte', ['infos' => $infos]);
    }
}