<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\PasswordRule;

class ResetForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['email'],
            'password' => [new PasswordRule()],
        ];
    }
}
