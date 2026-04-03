<?php

namespace Ivy\Controller;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Manager\TemplateManager;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Rule\InfoSettingRule;
use Ivy\Service\AssetPublisher;
use Ivy\View\View;

class TemplateController extends Controller
{
    protected Template $template;

    public function __construct()
    {
        parent::__construct();
        $this->template = new Template;
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

    public function post(): void
    {
        $this->template->authorize('post');

        foreach ($this->request->get('template') as $data) {
            try {
                $validated = new Validator($data, [
                    'value' => new InfoSettingRule,
                ]);

                if (! $validated->isValid()) {
                    foreach ($validated->getErrors() as $msg) {
                        $this->flashBag->add('error', $msg);
                    }

                    continue;
                }

                $template = (new Template)->where('id', $data['id'])->fetchOne();
                $template?->populate($data)->update();

                $publisher = new AssetPublisher;
                $publisher->publish('templates', (string) $template?->value);

            } catch (\Exception $e) {
                $this->flashBag->add('error', $e->getMessage());
            }
        }

        TemplateManager::init(true);

        $this->flashBag->add('success', 'Update successfully');
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
