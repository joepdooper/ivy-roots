<?php

namespace Ivy;

use GUMP;

class TemplateController extends Controller
{
    protected Template $template;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();
        $this->requireAdmin();

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
        $this->requireGet();
        $this->requireLogin();
        $this->requireAdmin();

        Template::view('admin/template.latte');
    }

}
