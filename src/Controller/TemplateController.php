<?php

namespace Ivy\Controller;

use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Model\Setting;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Path;
use Ivy\View\LatteView;

class TemplateController extends Controller
{
    protected Template $template;

    public function before(): void
    {
        if (!User::getAuth()->isLoggedIn() && Setting::getStash()['private']->bool) {
            if (Path::get('CURRENT_PAGE') != Path::get('BASE_PATH') . 'admin/login') {
                $this->redirect('admin/login');
            }
        }
    }

    public function post(): void
    {
        $this->authorize('post', Template::class);


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

    public function index(): void
    {
        $this->authorize('index', Template::class);

        LatteView::set('admin/template.latte');
    }

}
