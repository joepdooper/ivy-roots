<?php

namespace Ivy\Presentation\Form;

use Ivy\Domain\Model\InfoModel;
use Ivy\Shared\Base\Form;
use Ivy\Presentation\Rule\InfoSettingRule;
use Ivy\Presentation\Rule\UniqueRule;

class InfoForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'not_nullable', new InfoSettingRule(), new UniqueRule([InfoModel::class])],
            'value' => new InfoSettingRule(),
            'plugin_id' => 'numeric',
            'delete' => ['string']
        ];
    }
}
