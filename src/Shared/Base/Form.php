<?php

namespace Ivy\Shared\Base;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Shared\Presentation\Validation\ValidationResult;

abstract class Form
{
    protected Validator $validator;

    /**
     * @return array<string, array<int, string>>
     */
    protected function defaultRules(): array
    {
        return [
            'id' => ['numeric'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    abstract protected function rules(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): ValidationResult
    {
        $rules = array_merge(
            $this->defaultRules(),
            $this->rules()
        );

        $this->validator = new Validator($data, $rules);

        if (! $this->validator->isValid()) {
            return new ValidationResult(
                valid: false,
                errors: $this->validator->getErrors(),
                old: $data
            );
        }

        return new ValidationResult(
            valid: true,
            data: $this->validator->validated()
        );
    }
}
