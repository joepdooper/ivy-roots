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
        $this->settingForm = new SettingForm;
    }

    public function index(?string $url = null): void
    {
        $this->setting->authorize('index');

        $plugin_id = $url
            ? Plugin::where('url', $url)->value('id')
            : null;

        $settings = Setting::where('plugin_id', $plugin_id)->get();

        View::set('admin/setting.latte', ['settings' => $settings]);
    }

    public function add(mixed $data): void
    {
        $setting = new Setting;

        $setting->authorize('add');

        $setting->fill($data)->save();

        $this->flashBag->add(
            'success',
            'Setting ' . $setting->name . ' added successfully.'
        );
    }

    public function update(Setting|int $setting, mixed $data): void
    {
        if (is_int($setting)) {
            $setting = Setting::find($setting);
        }

        if (! $setting) {
            return;
        }

        $setting->fill($data);

        if (! $setting->isDirty()) {
            return;
        }

        $setting->authorize('update');

        $setting->save();

        $this->flashBag->add(
            'success',
            'Setting ' . $setting->name . ' updated successfully.'
        );
    }

    public function delete(Setting|int $setting): void
    {
        if (is_int($setting)) {
            $setting = Setting::find($setting);
        }

        $setting?->authorize('delete');

        if ($setting) {
            $setting->delete();

            $this->flashBag->add(
                'success',
                'Setting ' . $setting->name . ' deleted successfully.'
            );
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

                if (empty($data['id'])) {
                    $this->add($data);

                } elseif (isset($data['delete'])) {
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

        $this->redirect('admin/setting');
    }

    protected function resolveRefererContext(string $url = '', int $statusCode = 302): ?string
    {
        $refererPath = $this->getRefererPath();

        if ($refererPath != $this->setting->getPath()) {

            $segments = explode('/', (string) $refererPath);

            if ($segments[0] === 'plugin') {

                $this->setting->plugin_id = Plugin::where('url', $segments[1])
                    ->value('id');
            }
        }

        return $refererPath;
    }
}