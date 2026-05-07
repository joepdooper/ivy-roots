<?php

namespace Ivy\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\Presentation\Rule\UserImageRule;
use Ivy\Presentation\Rule\UserNameRule;

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
