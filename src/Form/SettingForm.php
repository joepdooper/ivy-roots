<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\InfoSettingRule;

class SettingForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule],
            'value' => new InfoSettingRule,
            'info' => ['string', 'max:50'],
            'plugin_id' => 'numeric',
            'delete' => ['string']
        ];
    }
}
