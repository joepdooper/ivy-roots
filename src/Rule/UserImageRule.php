<?php

namespace Ivy\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;

class UserImageRule implements Rule
{
    protected string $field;

    public function __construct(protected array $parameters = [])
    {
    }

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
        return "The field '{$this->field}' contains invalid characters.";
    }

}