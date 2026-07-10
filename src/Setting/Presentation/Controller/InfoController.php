<?php

namespace Ivy\Setting\Presentation\Controller;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Setting\Domain\Entity\Info;
use Ivy\Setting\Presentation\Form\InfoForm;
use Ivy\Shared\Base\Controller;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;

class InfoController extends Controller
{
    private Info $info;

    private InfoForm $infoForm;

    public function __construct()
    {
        parent::__construct();
        $this->info = new Info;
        $this->infoForm = new InfoForm;
    }

    /**
     * @throws AuthorizationException
     */
    public function index(?int $id = null): void
    {
        $this->info->authorize('index');

        $plugin_id = $id
            ? Plugin::where('url', $id)->value('id')
            : null;

        $infos = Info::where('plugin_id', $plugin_id)->get();

        View::render('admin/info.latte', ['infos' => $infos]);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(mixed $data): void
    {
        $info = new Info;

        $info->authorize('add');

        $info->fill($data)->save();

        $this->flashBag->add('success', 'Info '.$info->name.' added successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Info|int $info, mixed $data): void
    {
        if (is_int($info)) {
            $info = Info::find($info);
        }

        $info->fill($data);

        if (! $info->isDirty()) {
            return;
        }

        $info->authorize('update');

        $info->save();

        $this->flashBag->add(
            'success',
            'Info '.$info->name.' updated successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function delete(Info|int $info): void
    {
        if (is_int($info)) {
            $info = Info::find($info);
        }

        $info->authorize('delete');

        $info->delete();

        $this->flashBag->add(
            'success',
            'Info '.$info->name.' deleted successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function sync(): void
    {
        $this->info->authorize('sync');

        $errors = $old = [];

        foreach ($this->request->request->all('info') as $index => $data) {

            if (empty($data['name'])) {
                continue;
            }

            $result = $this->infoForm->validate($data);

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

        $this->redirect('admin/info');
    }

    protected function resolveRefererContext(): ?string
    {
        $refererPath = $this->getRefererPath();

        if ($refererPath != 'admin/info') {

            $segments = explode('/', (string) $refererPath);

            if ($segments[0] === 'plugin') {
                $id = Plugin::query()
                    ->where('url', $segments[1])
                    ->value('id');

                if ($id !== null) {
                    $this->info->plugin_id = (int) $id;
                }
            }
        }

        return $refererPath;
    }
}
