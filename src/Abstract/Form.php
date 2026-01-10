<?php

namespace Ivy\Abstract;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Validation\ValidationResult;

abstract class Form
{
    protected Validator $validator;

    abstract protected function rules(): array;

    public function validate($data): ValidationResult
    {
        $this->validator = new Validator($data, $this->rules());

        if (!$this->validator->isValid()) {
            return new ValidationResult(
                valid: false,
                errors: $this->validator->getErrors(),
            );
        }

        return new ValidationResult(
            valid: true,
            data: $this->validator->validated()
        );
    }
}
