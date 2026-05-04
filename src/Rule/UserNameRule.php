<?php

namespace Ivy\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;
use Ivy\Core\Language;

class UserNameRule implements Rule
{
    protected string $field;

    public function __construct(protected array $parameters = []) {}

    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        return preg_match('/^(?!.*[.-]{2})[a-zA-Z](?:[a-zA-Z0-9._-]{1,18}[a-zA-Z0-9])$/', $value) === 1;
    }

    public function message(): string
    {
        return Language::translate('form.rules.username', ['field' => $this->field]);
    }
}
