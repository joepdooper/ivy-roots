<?php

namespace Ivy\User\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\Shared\Presentation\Rule\UniqueRule;
use Ivy\User\Domain\Entity\User;
use Ivy\User\Presentation\Rule\PasswordRule;
use Ivy\User\Presentation\Rule\UserNameRule;

class RegisterForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => ['required', new UniqueRule([User::class]), new UserNameRule],
            'email' => ['required', 'email', new UniqueRule([User::class])],
            'password' => ['required', new PasswordRule],
        ];
    }
}
