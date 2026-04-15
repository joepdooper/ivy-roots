<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;

class PluginForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'id'=> ['numeric'],
            'url' => ['string'],
            'active' => ['in:0,1'],
            'delete' => ['string']
        ];
    }
}
