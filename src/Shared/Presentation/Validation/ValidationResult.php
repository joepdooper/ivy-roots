<?php

namespace Ivy\Shared\Presentation\Validation;

final readonly class ValidationResult
{
    public function __construct(
        public bool  $valid,
        public array $data = [],
        public array $errors = [],
        public array $old = [],
    ) {}
}
