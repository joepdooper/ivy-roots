<?php

namespace Ivy\Helper;

final class Raw
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    public static function make(string $expression): self
    {
        return new self($expression);
    }
}
