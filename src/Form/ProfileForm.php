<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\UserNameRule;
use Ivy\Rule\UserImageRule;

class ProfileForm extends Form
{
    protected function rules(): array
    {
        return [
            'username' => ['required', 'not_nullable', new UserNameRule()],
            'email' => ['required', 'not_nullable', 'email'],
            'delete_user_image' => ['bool'],
            'user_image' => ['file', new UserImageRule()],
        ];
    }
}
