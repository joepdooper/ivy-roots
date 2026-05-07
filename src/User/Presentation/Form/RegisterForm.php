<?php

namespace Ivy\Presentation\Form;

use Ivy\Domain\Model\UserModel;
use Ivy\Shared\Base\Form;
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
            'username' => ['required', new UniqueRule([UserModel::class]), new UserNameRule()],
            'email' => ['required', 'email', new UniqueRule([UserModel::class])],
            'password' => ['required', new PasswordRule()],
        ];
    }
}
