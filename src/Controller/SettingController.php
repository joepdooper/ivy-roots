<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\SettingForm;
use Ivy\Model\Plugin;
use Ivy\Model\Setting;
use Ivy\View\View;

class SettingController extends Controller
{
    private Setting $setting;
    private SettingForm $settingForm;

    public function __construct()
    {
        parent::__construct();
        $this->setting = new Setting;
        $this->settingForm = New SettingForm;
    }

    public function index(?int $id = null): void
    {
        $this->setting->authorize('index');

        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $settings = $this->setting->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    public function sync(): void
    {
        $this->setting->authorize('sync');

        $errors = $old = [];

        foreach ($this->request->get('setting') as $index => $data) {

            if (empty($data['name'])) {
                continue;
            }

            $result = $this->settingForm->validate($data);

            if (! $result->valid) {
                $errors['setting'][$index] = $result->errors;
                $old['setting'][$index] = $result->old;
            } else {
                $setting = ! empty($data['id'])
                    ? (new Setting)->where('id', $data['id'])->fetchOne()
                    : new Setting;

                if (isset($data['delete']) && ! empty($data['id'])) {
                    $setting?->delete();
                    $this->flashBag->add('success', 'Setting ' . $setting->name . ' deleted successfully.');
                } else {
                    $setting?->populate($data)->save();
                }
            }
        }

        if (! empty($errors)) {
            $this->flashBag->set('errors', $errors);
            $this->flashBag->set('old', $old);
        } else {
            $this->flashBag->add('success', 'Settings updated successfully.');
        }

        $this->redirect($this->resolveRefererContext() ?? '');
    }

    protected function resolveRefererContext(string $url = '', int $statusCode = 302): ?string
    {
        $refererPath = $this->getRefererPath();
        if ($refererPath != $this->setting->getPath()) {
            $segments = explode('/', (string) $refererPath);
            if ($segments[0] === 'plugin') {
                $this->setting->plugin_id = (new Plugin)->where('url', $segments[1])->fetchOne()?->getId();
            }
        }

        return $refererPath;
    }
}
