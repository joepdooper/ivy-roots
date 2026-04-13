<?php

namespace Ivy\Controller;

use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\TemplateForm;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Service\AssetPublisher;
use Ivy\View\View;

class TemplateController extends Controller
{
    protected Template $template;
    protected TemplateForm $templateForm;

    public function __construct()
    {
        parent::__construct();
        $this->template = new Template;
        $this->templateForm = new TemplateForm;
    }

    public function before(): void
    {
        if (! User::getAuth()->isLoggedIn() && Setting::stashGet('private')->bool) {
            if (! $this->isAlwaysPublicPath(Path::get('CURRENT_PAGE'))) {
                $this->redirect('user/login');
            }
        }
    }

    public function index(): void
    {
        $this->template->authorize('index');

        View::set('admin/template.latte', [
            'templateBase' => basename((string) TemplateManager::getTemplateBase()),
            'templateSub' => basename((string) TemplateManager::getTemplateSub()),
        ]);
    }

    public function update(Template|int $template, mixed $data): void
    {
        if(is_int($template)) {
            $template = (new Template)->where('id', $template)->fetchOne();
        }

        if($template && $template->isDirty($data)) {
            $template->authorize('update');
            $template->populate($data)->update();
            $publisher = new AssetPublisher;
            $publisher->publish('templates', $template->value);
            $this->flashBag->add('success', $template->type . '-template updated successfully.');
        }
    }

    public function sync(): void
    {
        $this->template->authorize('sync');

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

        $this->redirect('admin/template');
    }

    private function isAlwaysPublicPath(string $current): bool
    {
        $allowed = [
            Path::get('PUBLIC_URL').'user/login',
            Path::get('PUBLIC_URL').'user/reset',
            Path::get('PUBLIC_URL').'user/register',
        ];

        foreach ($allowed as $prefix) {
            if ($current === $prefix || str_starts_with($current, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
