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
                        Message::add($string);
                    }
                }
            } catch (\Exception $e) {
                Message::add($e->getMessage());
            }
        }

        Message::add('Update successfully', Path::get('BASE_PATH') . 'admin/template');

    }

}
