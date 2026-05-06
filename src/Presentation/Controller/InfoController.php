<?php

namespace Ivy\Presentation\Controller;

use Ivy\Shared\Base\Controller;
use Ivy\Presentation\Form\InfoForm;
use Ivy\Domain\Entity\InfoEntity;
use Ivy\Domain\Entity\PluginEntity;
use Ivy\Presentation\View\View;

class InfoController extends Controller
{
    private InfoEntity $info;
    private InfoForm $infoForm;

    public function __construct()
    {
        parent::__construct();
        $this->info = new InfoEntity;
        $this->infoForm = new InfoForm;
    }

    public function index(?int $id = null): void
    {
        $this->info->authorize('index');

        $plugin_id = $id
            ? PluginEntity::where('url', $id)->value('id')
            : null;

        $infos = InfoEntity::where('plugin_id', $plugin_id)->get();

        View::render('admin/info.latte', ['infos' => $infos]);
    }

    public function add(mixed $data): void
    {
        $info = new InfoEntity;

        $info->authorize('add');

        $info->fill($data)->save();

        $this->flashBag->add('success', 'Info ' . $info->name . ' added successfully.');
    }

    public function update(InfoEntity|int $info, mixed $data): void
    {
        if (is_int($info)) {
            $info = InfoEntity::find($info);
        }

        if (! $info) {
            return;
        }

        $info->fill($data);

        if (! $info->isDirty()) {
            return;
        }

        $info->authorize('update');
        
        $info->save();

        $this->flashBag->add(
            'success',
            'Info ' . $info->name . ' updated successfully.'
        );
    }

    public function delete(InfoEntity|int $info): void
    {
        if (is_int($info)) {
            $info = InfoEntity::find($info);
        }

        $info?->authorize('delete');

        if ($info) {
            $info->delete();

            $this->flashBag->add(
                'success',
                'Info ' . $info->name . ' deleted successfully.'
            );
        }
    }

    public function sync(): void
    {
        $this->info->authorize('sync');

        $errors = $old = [];

        foreach ($this->request->get('info') as $index => $data) {

            if (empty($data['name'])) {
                continue;
            }

            $result = $this->infoForm->validate($data);

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

        $this->redirect('admin/info');
    }

    protected function resolveRefererContext(string $url = '', int $statusCode = 302): ?string
    {
        $refererPath = $this->getRefererPath();

        if ($refererPath != $this->info->getPath()) {

            $segments = explode('/', (string) $refererPath);

            if ($segments[0] === 'plugin') {

                $this->info->plugin_id = PluginEntity::where('url', $segments[1])
                    ->value('id');
            }
        }

        return $refererPath;
    }
}
