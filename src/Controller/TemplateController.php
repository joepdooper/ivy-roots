<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Profile;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Core\Path;
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
        if (!User::getAuth()->isLoggedIn() && Setting::getStash()['private']->bool) {
            if (!$this->isAlwaysPublicPath(Path::get('CURRENT_PAGE'))) {
                $this->redirect('user/login');
            }
        }
    }

    public function index(): void
    {
        $this->template->policy('index');

        View::set('admin/template.latte');
    }

    public function root(): void
    {
        View::set('body.latte');
    }

    public function post(): void
    {
        $this->template->policy('post');

        $templates_data = $this->request->get('template') ?? '';

        foreach ($templates_data as $template_data) {
            try {
                $validated = GUMP::is_valid($template_data, [
                    'value' => 'regex,/^[a-zA-Z0-9\-_ \x2C\/:.]+$/'
                ]);
                if ($validated === true) {
                    $this->template = new Template;
                    $this->template->save($template_data);
                } else {
                    foreach ($validated as $string) {
                        $this->flashBag->add('error', $string);
                    }
                }
            } catch (\Exception $e) {
                $this->flashBag->add('error', $e->getMessage());
            }
        }

        $this->flashBag->add('success', 'Update successfully');
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
