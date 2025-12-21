<?php

namespace Ivy\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;

class AllowedCharsRule implements Rule
{
    protected $field;

    public function __construct(protected array $parameters = [])
    {
    }

    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        // Regex: letters, digits, dash, underscore, space, comma, slash, colon, dot
        return preg_match('/^[a-zA-Z0-9\-_ \x2C\/:.]+$/', $value) === 1;
    }

    public function message(): string
    {
        return "The field '{$this->field}' contains invalid characters.";
    }

}