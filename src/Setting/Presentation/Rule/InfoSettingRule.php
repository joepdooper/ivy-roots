<?php

namespace Ivy\Setting\Presentation\Rule;

use BlakvGhost\PHPValidator\Contracts\Rule;
use Ivy\Shared\Core\Language;

class InfoSettingRule implements Rule
{
    protected string $field;

    /**
     * @param array<int, mixed> $parameters
     */
    public function __construct(protected array $parameters = []) {}

    /**
     * @param array<string, mixed> $data
     */
    public function passes(string $field, $value, array $data): bool
    {
        $this->field = $field;

        return preg_match('/^[a-zA-Z0-9\-_ \x2C\/:.]+$/', $value) === 1;
    }

    public function message(): string
    {
        return Language::translate('form.rules.name', ['field' => $this->field]);
    }
}
