<?php

namespace Ivy\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\Domain\Entity\UserEntity;
use Ivy\Presentation\Rule\PasswordRule;
use Ivy\Presentation\Rule\UniqueRule;
use Ivy\Presentation\Rule\UserNameRule;

class RegisterForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => ['required', new UniqueRule([UserEntity::class]), new UserNameRule()],
            'email' => ['required', 'email', new UniqueRule([UserEntity::class])],
            'password' => ['required', new PasswordRule()],
        ];
    }
}
