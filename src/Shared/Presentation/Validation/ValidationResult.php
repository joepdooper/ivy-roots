<?php

namespace Ivy\Shared\Presentation\Validation;

final readonly class ValidationResult
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $old
     */
    public function __construct(
        public bool  $valid,
        public array $data = [],
        public array $errors = [],
        public array $old = [],
    ) {}
}
