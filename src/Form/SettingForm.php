<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\InfoSettingRule;

class SettingForm extends Form
{
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule()],
            'value' => new InfoSettingRule(),
            'plugin_id' => 'numeric'
        ];
    }
}
