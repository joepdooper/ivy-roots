<?php

namespace Ivy\View\Engine;

interface EngineInterface
{
    public function render(string $template, array $params = [], ?string $block = null): void;
}