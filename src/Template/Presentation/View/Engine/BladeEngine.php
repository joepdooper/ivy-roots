<?php

namespace Ivy\Template\Presentation\View\Engine;

use Exception;
use Ivy\Template\Application\Contracts\ViewEngineInterface;
use Ivy\User\Application\Service\AuthService;
use Latte\Extension;
use Symfony\Component\HttpFoundation\Request;

class BladeEngine implements ViewEngineInterface
{
    /** @phpstan-ignore-next-line property.unused */
    private AuthService $auth;

    /** @phpstan-ignore-next-line property.unused */
    private Request $request;

    public function __construct(AuthService $auth, Request $request)
    {
        $this->auth = $auth;
        $this->request = $request;
    }

    /**
     * @throws Exception
     */
    public function boot(): void
    {
        throw new Exception('Blade engine is not implemented yet.');
    }

    /**
     * @param  array<string, mixed>  $params
     */
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
