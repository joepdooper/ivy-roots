<?php

namespace Ivy\Template\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\Shared\Presentation\Rule\UniqueRule;
use Ivy\Template\Domain\Entity\Template;

class TemplateDataForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'package' => ['required', 'string', new UniqueRule([Template::class])],
            'interface' => ['required', 'string'],
            'version' => ['string'],
            'version_channel' => ['string'],
            'description' => ['string'],
            'type' => ['alpha'],
            'url' => ['required', 'string', new UniqueRule([Template::class])],
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