<?php

namespace Ivy\Template\Presentation\View\Engine;

use Ivy\Template\Contracts\ViewEngineInterface;

class BladeEngine implements ViewEngineInterface
{

    public function __construct()
    {
    }

    /**
     * @throws \Exception
     */
    public function boot(): void
    {
        $this->registerFunctions();
        $this->registerExtensions();
        $this->registerProviders();
    }

    public function render(string $template, array $params = [], ?string $block = null): void
    {
    }

    public function addFunction(string $name, callable $callback): void
    {
    }

    public function addExtension(object $extension): void
    {
    }

    private function registerFunctions(): void
    {
    }

    private function registerExtensions(): void
    {
    }

    private function registerProviders(): void
    {
    }
}
