<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;

class LoginForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }
}
