<?php

namespace Ivy\Abstract;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Validation\ValidationResult;

abstract class Form
{
    protected Validator $validator;

    /** @return array<string, mixed> */
    abstract protected function rules(): array;

    /**
     * @param  mixed[]  $data
     */
    public function validate(array $data): ValidationResult
    {
        $this->validator = new Validator($data, $this->rules());

        if (! $this->validator->isValid()) {
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
