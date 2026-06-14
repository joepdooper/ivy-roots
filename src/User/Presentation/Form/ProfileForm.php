<?php

namespace Ivy\User\Presentation\Form;

use Ivy\Shared\Base\Form;
use Ivy\User\Presentation\Rule\UserImageRule;
use Ivy\User\Presentation\Rule\UserNameRule;

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
            'delete_image' => ['in:delete'],
            'image' => ['file', new UserImageRule],
        ];
    }
}
