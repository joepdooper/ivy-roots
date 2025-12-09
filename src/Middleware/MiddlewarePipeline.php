<?php

namespace Ivy\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function handle(Request $request, callable $controller): ?Response
    {
        $next = array_reduce(
            array_reverse($this->middlewares),
            fn($next, MiddlewareInterface $middleware) =>
            fn(Request $req) => $middleware->handle($req, $next),
            $controller
        );

        return $next($request);
    }
}