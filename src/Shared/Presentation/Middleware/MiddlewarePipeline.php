<?php

namespace Ivy\Shared\Presentation\Middleware;

use Symfony\Component\HttpFoundation\Request;

class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(Request $request, callable $controller): void
    {
        foreach ($this->middlewares as $middleware) {
            $middleware->handle($request);
        }

        $controller($request);
    }
}
