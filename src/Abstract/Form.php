<?php

namespace Ivy\Abstract;

use BlakvGhost\PHPValidator\Validator;
use Ivy\Validation\ValidationResult;

abstract class Form
{
    protected Validator $validator;

    public function __construct(protected array $data)
    {
        $this->validator = new Validator($this->data, $this->rules());
    }

    abstract protected function rules(): array;

    public function validate(): ValidationResult
    {
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
