<?php

namespace Ivy\Presentation\Controller;

use Ivy\Domain\Exception\AuthorizationException;
use Ivy\Domain\Model\InfoModel;
use Ivy\Domain\Model\PluginModel;
use Ivy\Shared\Base\Controller;
use Ivy\Presentation\Form\InfoForm;
use Ivy\Presentation\View\View;

class InfoController extends Controller
{
    private InfoModel $info;
    private InfoForm $infoForm;

    public function __construct()
    {
        parent::__construct();
        $this->info = new InfoModel;
        $this->infoForm = new InfoForm;
    }

    /**
     * @throws AuthorizationException
     */
    public function index(?int $id = null): void
    {
        $this->info->authorize('index');

        $plugin_id = $id
            ? PluginModel::where('url', $id)->value('id')
            : null;

        $infos = InfoModel::where('plugin_id', $plugin_id)->get();

        View::render('admin/info.latte', ['infos' => $infos]);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(mixed $data): void
    {
        $info = new InfoModel;

        $info->authorize('add');

        $info->fill($data)->save();

        $this->flashBag->add('success', 'Info ' . $info->name . ' added successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(InfoModel|int $info, mixed $data): void
    {
        if (is_int($info)) {
            $info = InfoModel::find($info);
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

    /**
     * @throws AuthorizationException
     */
    public function delete(InfoModel|int $info): void
    {
        if (is_int($info)) {
            $info = InfoModel::find($info);
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

    /**
     * @throws AuthorizationException
     */
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

                if (empty($result->data['id'])) {
                    $this->add($result->data);

                } elseif (isset($result->data['delete'])) {
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

        $this->redirect('admin/info');
    }

    protected function resolveRefererContext(string $url = '', int $statusCode = 302): ?string
    {
        $refererPath = $this->getRefererPath();

        if ($refererPath != $this->info->getPath()) {

            $segments = explode('/', (string) $refererPath);

            if ($segments[0] === 'plugin') {

                $this->info->plugin_id = PluginModel::where('url', $segments[1])
                    ->value('id');
            }
        }

        return $refererPath;
    }
}
