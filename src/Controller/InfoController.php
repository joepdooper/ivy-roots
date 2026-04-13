<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Form\InfoForm;
use Ivy\Model\Info;
use Ivy\Model\Plugin;
use Ivy\View\View;

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

    public function index(?int $id = null): void
    {
        $this->info->authorize('index');

        $plugin_id = $id ? (new Plugin)->where('url', $id)->fetchOne()?->getId() : null;
        $infos = $this->info->where('plugin_id', $plugin_id)->fetchAll();
        View::set('admin/info.latte', ['infos' => $infos]);
    }

    public function add(mixed $data): void
    {
        $info = new Info();

        $info->authorize('add');

        $info->populate($data)->save();
        $this->flashBag->add('success', 'Info ' . $info->name . ' added successfully.');
    }

    public function update(Info|int $info, mixed $data): void
    {
        if(is_int($info)) {
            $info = (new Info)->where('id', $info)->fetchOne();
        }

        $info?->authorize('update');

        if($info && $info->isDirty($data)) {
            $info->populate($data)->update();
            $this->flashBag->add('success', 'Info ' . $info->name . ' updated successfully.');
        }
    }

    public function delete(Info|int $info): void
    {
        if(is_int($info)) {
            $info = (new Info)->where('id', $info)->fetchOne();
        }

        $info?->authorize('delete');

        if($info){
            $info->delete();
            $this->flashBag->add('success', 'Info ' . $info->name . ' deleted successfully.');
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

            $result = (new InfoForm)->validate($data);

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
        if ($refererPath != $this->info->getPath()) {
            $segments = explode('/', (string) $refererPath);
            if ($segments[0] === 'plugin') {
                $this->info->plugin_id = (new Plugin)->where('url', $segments[1])->fetchOne()?->getId();
            }
        }

        return $refererPath;
    }
}
