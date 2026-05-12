<?php

namespace Ivy\User\Presentation\Form;

use Ivy\Plugin\Domain\Entity\UserModel;
use Ivy\Shared\Base\Form;
use Ivy\Setting\Presentation\Rule\PasswordRule;
use Ivy\Setting\Presentation\Rule\UniqueRule;
use Ivy\Setting\Presentation\Rule\UserNameRule;

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
