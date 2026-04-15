<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;

class UserForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'id'=> ['required', 'numeric'],
            'super_admin' => ['in:0,1'],
            'admin' => ['in:0,1'],
            'editor' => ['in:0,1'],
        ];
    }
}
