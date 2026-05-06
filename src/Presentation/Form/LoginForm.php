<?php

namespace Ivy\Presentation\Form;

use Ivy\Shared\Base\Form;

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
