<?php

namespace Ivy\Shared\Contract;

interface ViewEngineInterface
{
    public function render(string $template, array $params = [], ?string $block = null): void;

    public function addFunction(string $name, callable $callback): void;

    public function addExtension(object $extension): void;

    public function boot(): void;
}
