<?php

namespace Ivy\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;

class UserNameRule implements Rule
{
    protected string $field;

    public function __construct(protected array $parameters = [])
    {
    }

    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        return preg_match('/^(?!.*[.-]{2})[a-zA-Z](?:[a-zA-Z0-9._-]{1,18}[a-zA-Z0-9])$/', $value) === 1;
    }

    public function message(): string
    {
        return "The {$this->field} must be 3–20 characters long, start with a letter, and may only contain letters, numbers, dots, underscores, or dashes.";
    }

}