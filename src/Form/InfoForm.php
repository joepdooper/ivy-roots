<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Model\Info;
use Ivy\Rule\InfoSettingRule;
use Ivy\Rule\UniqueRule;

class InfoForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule, new UniqueRule([Info::class])],
            'value' => new InfoSettingRule,
            'plugin_id' => 'numeric',
            'delete' => ['string']
        ];
    }
}
