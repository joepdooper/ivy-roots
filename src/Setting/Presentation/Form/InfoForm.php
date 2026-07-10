<?php

namespace Ivy\Setting\Presentation\Form;

use Ivy\Setting\Domain\Entity\Info;
use Ivy\Setting\Presentation\Rule\InfoSettingRule;
use Ivy\Shared\Base\Form;
use Ivy\Shared\Presentation\Rule\UniqueRule;

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
            'info' => ['string', 'max:50'],
            'plugin_id' => 'numeric',
            'delete' => ['string'],
        ];
    }
}
