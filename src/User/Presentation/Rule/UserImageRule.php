<?php

namespace Ivy\User\Presentation\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;
use Ivy\Shared\Core\Language;

class UserImageRule implements Rule
{
    protected string $field;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(protected array $parameters = []) {}

    /**
     * @param array<string, mixed> $data
     */
    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        if (isset($_FILES[$field]) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
            $fileExtension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);

            return in_array(strtolower($fileExtension), $allowedExtensions, true);
        }

        return false;
    }

    public function message(): string
    {
        return Language::translate('form.rules.image', ['field' => $this->field]);
    }
}
