<?php

namespace Ivy\Setting\Presentation\Controller;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Setting\Presentation\Form\SettingForm;
use Ivy\Shared\Base\Controller;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;

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

    /**
     * @throws AuthorizationException
     */
    public function index(?string $url = null): void
    {
        $this->setting->authorize('index');

        $plugin_id = $url
            ? Plugin::where('url', $url)->value('id')
            : null;

        $settings = Setting::where('plugin_id', $plugin_id)->get();

        View::render('admin/setting.latte', ['settings' => $settings]);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(mixed $data): void
    {
        $setting = new Setting;

        $setting->authorize('add');

        $setting->fill($data)->save();

        $this->flashBag->add(
            'success',
            'Setting '.$setting->name.' added successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Setting|int $setting, mixed $data): void
    {
        if (is_int($setting)) {
            $setting = Setting::find($setting);
        }

        $setting->fill($data);

        if (! $setting->isDirty()) {
            return;
        }

        $setting->authorize('update');

        $setting->save();

        $this->flashBag->add(
            'success',
            'Setting '.$setting->name.' updated successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function delete(Setting|int $setting): void
    {
        if (is_int($setting)) {
            $setting = Setting::find($setting);
        }

        $setting->authorize('delete');

        $setting->delete();

        $this->flashBag->add(
            'success',
            'Setting '.$setting->name.' deleted successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function sync(): void
    {
        $this->setting->authorize('sync');

        $errors = $old = [];

        foreach ($this->request->request->all('setting') as $index => $data) {

            if (empty($data['name']) && ! isset($data['id'])) {
                continue;
            }

            $result = $this->settingForm->validate($data);

            if ($result->valid) {

                if (empty($result->data['id'])) {
                    $this->add($result->data);

                } elseif (isset($data['delete'])) {
                    $this->delete($result->data['id']);

                } else {
                    $this->update($result->data['id'], $result->data);
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

    protected function resolveRefererContext(): ?string
    {
        $refererPath = $this->getRefererPath();

        if ($refererPath != 'admin/setting') {

            $segments = explode('/', (string) $refererPath);

            if ($segments[0] === 'plugin') {
                $id = Plugin::query()
                    ->where('url', $segments[1])
                    ->value('id');

                if ($id !== null) {
                    $this->setting->plugin_id = (int) $id;
                }
            }
        }

        return $refererPath;
    }
}
