<?php

namespace Ivy\Setting\Presentation\Form;

use Ivy\Setting\Domain\Entity\Info;
use Ivy\Shared\Base\Form;
use Ivy\Setting\Presentation\Rule\InfoSettingRule;
use Ivy\Shared\Presentation\Rule\UniqueRule;

class InfoForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule(), new UniqueRule([Info::class])],
            'value' => new InfoSettingRule(),
            'plugin_id' => 'numeric',
            'delete' => ['string']
        ];
    }
}
