<?php

namespace Ivy\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\Domain\Entity\SettingEntity;
use Ivy\Presentation\Rule\InfoSettingRule;
use Ivy\Presentation\Rule\UniqueRule;

class SettingForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule, new UniqueRule([SettingEntity::class])],
            'value' => new InfoSettingRule,
            'info' => ['string', 'max:50'],
            'plugin_id' => 'numeric',
            'delete' => ['string']
        ];
    }
}
