<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\UserImageRule;
use Ivy\Rule\UserNameRule;

class TemplateForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'id'=> ['numeric'],
            'value' => ['required', 'not_nullable', 'string'],
        ];
    }
}
