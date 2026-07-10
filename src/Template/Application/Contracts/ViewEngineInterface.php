<?php

namespace Ivy\Template\Application\Contracts;

use Latte\Extension;

interface ViewEngineInterface
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function render(string $template, array $params = [], ?string $block = null): void;

    public function addFunction(string $name, callable $callback): void;

    public function addExtension(Extension $extension): void;

    public function boot(): void;
}
