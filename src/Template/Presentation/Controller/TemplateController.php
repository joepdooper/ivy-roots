<?php

namespace Ivy\Presentation\Controller;

use Ivy\Application\Service\AssetPublisherApplicationService;
use Ivy\Domain\Model\SettingModel;
use Ivy\Domain\Model\TemplateModel;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Path;
use Ivy\Presentation\Form\TemplateForm;
use Ivy\Infrastructure\Manager\TemplateManager;
use Ivy\Presentation\View\View;

class TemplateController extends Controller
{
    protected TemplateModel $template;
    protected TemplateForm $templateForm;

    public function __construct()
    {
        parent::__construct();
        $this->template = new TemplateModel();
        $this->templateForm = new TemplateForm();
    }

    public function before(): void
    {
        if (! $this->authService->isLoggedIn() && SettingModel::stashGet('private')->bool) {

            if (! $this->isAlwaysPublicPath(Path::get('CURRENT_PAGE'))) {
                $this->redirect('user/login');
            }
        }
    }

    public function index(): void
    {
        $this->template->authorize('index');

        View::render('admin/template.latte', [
            'templateBase' => basename((string) TemplateManager::getTemplateBase()),
            'templateSub'  => basename((string) TemplateManager::getTemplateSub()),
        ]);
    }

    public function update(TemplateModel|int $template, mixed $data): void
    {
        if (is_int($template)) {
            $template = TemplateModel::find($template);
        }

        if (! $template) {
            return;
        }

        $template->fill($data);

        if (! $template->isDirty()) {
            return;
        }

        $template->authorize('update');

        $template->save();

        $this->flashBag->add(
            'success',
            $template->type . '-template updated successfully.'
        );
    }

    public function sync(): void
    {
        $this->template->authorize('sync');

        $errors = $old = [];

        foreach ($this->request->get('template') as $index => $data) {

            $result = $this->templateForm->validate($data);

            if ($result->valid) {
                $this->update($result->data['id'], $result->data);
            } else {
                $errors[$index] = $result->errors;
                $old[$index] = $result->old;
            }
        }

        if (! empty($errors)) {
            $this->flashBag->set('errors', $errors);
            $this->flashBag->set('old', $old);
        }

        TemplateManager::init(true);
        new AssetPublisherApplicationService()->publishTemplate();

        $this->redirect('admin/template');
    }

    private function isAlwaysPublicPath(string $current): bool
    {
        $allowed = [
            Path::get('PUBLIC_URL') . 'user/login',
            Path::get('PUBLIC_URL') . 'user/reset',
            Path::get('PUBLIC_URL') . 'user/register',
        ];

        foreach ($allowed as $prefix) {
            if ($current === $prefix || str_starts_with($current, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
