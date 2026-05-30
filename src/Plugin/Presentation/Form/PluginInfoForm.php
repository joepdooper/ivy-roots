<?php

namespace Ivy\Plugin\Presentation\Form;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Shared\Base\Form;
use Ivy\Shared\Presentation\Rule\UniqueRule;

class PluginInfoForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name'=> ['required', 'string', new UniqueRule([Plugin::class])],
            'interface' => ['required', 'string'],
            'version' => ['string'],
            'description' => ['string'],
            'type' => ['alpha'],
            'url' => ['alpha_num'],
            'collection.*' => ['string'],
            'settings.*.name' => ['string'],
            'settings.*.info' => ['string'],
            'actions.*' => [],
            'dependencies.*' => ['string'],
        ];
    }
}
