<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Model\Info;
use Ivy\Model\User;
use Ivy\Rule\PasswordRule;
use Ivy\Rule\UniqueRule;
use Ivy\Rule\UserNameRule;

class RegisterForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => ['required', new UniqueRule([User::class]), new UserNameRule()],
            'email' => ['required', 'email', new UniqueRule([User::class])],
            'password' => ['required', new PasswordRule()],
        ];
    }
}
