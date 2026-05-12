<?php

namespace Ivy\Shared\Presentation\Middleware;

use Symfony\Component\HttpFoundation\Request;

interface MiddlewareInterface
{
    public function handle(Request $request): void;
}
