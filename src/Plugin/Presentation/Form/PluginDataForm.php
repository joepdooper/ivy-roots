<?php

namespace Ivy\Plugin\Presentation\Form;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Shared\Base\Form;
use Ivy\Shared\Presentation\Rule\UniqueRule;

class PluginDataForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'package' => ['required', 'string', new UniqueRule([Plugin::class])],
            'interface' => ['required', 'string'],
            'version' => ['string'],
            'version_channel' => ['string'],
            'description' => ['string'],
            'type' => ['alpha'],
            'url' => ['required', 'string', new UniqueRule([Plugin::class])],
            'license' => ['string'],
            'homepage' => ['string'],
//            'keywords.*' => ['string'],
//            'collection.*' => ['string'],
//            'settings.*.name' => ['string'],
//            'settings.*.info' => ['string'],
//            'actions.*' => [],
//            'dependencies.*' => ['string'],
        ];
    }
}