<?php

namespace Ivy\Validation;

final class ValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly array $data = [],
        public readonly array $errors = [],
        public readonly array $old = [],
    ) {}
}