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

    public function index(?string $url = null): void
    {
        $this->setting->authorize('index');

        $plugin_id = $url ? (new Plugin)->where('url', $url)->fetchOne()?->getId() : null;
        $settings = $this->setting->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    public function add(mixed $data): void
    {
        $setting = new Setting();

        $setting->authorize('add');

        $setting->populate($data)->save();
        $this->flashBag->add('success', 'Setting ' . $setting->name . ' added successfully.');
    }

    public function update(Setting|int $setting, mixed $data): void
    {
        if(is_int($setting)) {
            $setting = (new Setting)->where('id', $setting)->fetchOne();
        }

        $setting?->authorize('update');

        if($setting && $setting->isDirty($data)) {
            $setting->populate($data)->update();
            $this->flashBag->add('success', 'Setting ' . $setting->name . ' updated successfully.');
        }
    }

    public function delete(Setting|int $setting): void
    {
        if(is_int($setting)) {
            $setting = (new Setting)->where('id', $setting)->fetchOne();
        }

        $setting?->authorize('delete');

        if($setting){
            $setting->delete();
            $this->flashBag->add('success', 'Setting ' . $setting->name . ' deleted successfully.');
        }
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

            if ($result->valid) {
                if(empty($data['id'])){
                    $this->add($data);
                } elseif(isset($data['delete'])) {
                    $this->delete($data['id']);
                } else {
                    $this->update($data['id'], $data);
                }
            } else {
                $errors[$index] = $result->errors;
                $old[$index] = $result->old;
            }
        }

        if (! empty($errors)) {
            $this->flashBag->set('errors', $errors);
            $this->flashBag->set('old', $old);
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
