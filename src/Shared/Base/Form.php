<?php

namespace Ivy\Shared\Base;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Presentation\Validation\ValidationResult;

abstract class Form
{
    protected Validator $validator;

    protected function defaultRules(): array
    {
        return [
            'id' => ['numeric'],
        ];
    }

    abstract protected function rules(): array;

    public function validate($data): ValidationResult
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
