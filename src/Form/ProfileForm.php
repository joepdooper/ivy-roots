<?php

namespace Ivy\Form;

use Ivy\Abstract\Form;
use Ivy\Rule\UserImageRule;
use Ivy\Rule\UserNameRule;

class ProfileForm extends Form
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'username' => ['required', 'not_nullable', new UserNameRule],
            'email' => ['required', 'not_nullable', 'email'],
            'delete_user_image' => ['bool'],
            'user_image' => ['file', new UserImageRule],
        ];
    }
}
