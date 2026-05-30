<?php

namespace Ivy\Template\Presentation\View\Engine;

use Exception;
use Ivy\Template\Application\Contracts\ViewEngineInterface;
use Ivy\User\Application\Service\AuthService;
use Latte\Extension;

class BladeEngine implements ViewEngineInterface
{
    private AuthService $auth;


    public function setAuth(AuthService $auth): void
    {
        $this->auth = $auth;
    }
    /**
     * @throws Exception
     */
    public function boot(): void
    {
        throw new Exception('Blade engine is not implemented yet.');
    }

    public function render(string $template, array $params = [], ?string $block = null): void
    {
        // TODO: Implement render() method.
    }

    public function addFunction(string $name, callable $callback): void
    {
        // TODO: Implement addFunction() method.
    }

    public function addExtension(Extension $extension): void
    {
        // TODO: Implement addExtension() method.
    }
}
