<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Form\InfoForm;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
use Ivy\View\View;

class InfoController extends SettingController
{
    private Info $info;

    public function __construct()
    {
        parent::__construct();
        $this->info = new Info;
    }

    public function index($id = null): void
    {
        $this->info->authorize('index');

        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $infos = $this->info->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/info.latte', ['infos' => $infos]);
    }

    public function post(): void
    {
        $this->info->authorize('post');

        $redirect = $this->resolveRefererContext();

        foreach ($this->request->get('info') as $data) {

            if (empty($data['name'])) continue;

            $result = (new InfoForm)->validate($data);

            if (!$result->valid) {
                $this->flashBag->set('errors', $result->errors);
                $this->flashBag->set('old', $result->old);
                $this->redirect($redirect);
            } else {
                $info = !empty($data['id'])
                    ? (new Info)->where('id', $data['id'])->fetchOne()
                    : new Info();

                if (isset($data['delete']) && !empty($data['id'])) {
                    $info?->delete();
                } else {
                    $info->populate($data)->save();
                }
            }
        }

        $this->flashBag->add('success', 'Update successfully');
        $this->redirect($redirect);
    }
}