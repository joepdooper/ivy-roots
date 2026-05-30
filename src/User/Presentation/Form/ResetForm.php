<?php

namespace Ivy\User\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\User\Presentation\Rule\PasswordRule;

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
