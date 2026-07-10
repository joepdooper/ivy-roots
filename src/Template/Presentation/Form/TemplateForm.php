<?php

namespace Ivy\Template\Presentation\Form;

use Ivy\Shared\Base\Form;

class TemplateForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'id' => ['numeric'],
            'value' => ['required', 'not_nullable', 'string'],
        ];
    }
}
