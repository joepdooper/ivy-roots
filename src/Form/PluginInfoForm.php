<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;

class PluginInfoForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name'=> ['required', 'alpha_num'],
            'interface' => ['required', 'string'],
            'version' => ['string'],
            'description' => ['string'],
            'type' => ['alpha'],
            'url' => ['alpha_num'],
            'collection.*' => ['alpha_num'],
            'settings.*.name' => ['alpha_num'],
            'settings.*.info' => ['string'],
            'actions.*' => [],
            'dependencies.*' => ['alpha_num'],
        ];
    }
}