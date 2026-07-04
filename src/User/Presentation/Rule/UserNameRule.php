<?php

namespace Ivy\User\Presentation\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;
use Ivy\Shared\Core\Language;

class UserNameRule implements Rule
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

        return preg_match('/^(?!.*[.-]{2})[a-zA-Z][a-zA-Z0-9._-]{1,18}[a-zA-Z0-9]$/', $value) === 1;
    }

    public function message(): string
    {
        return Language::translate('form.rules.username', ['field' => $this->field]);
    }
}
