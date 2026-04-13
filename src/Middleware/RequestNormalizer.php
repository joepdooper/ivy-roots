<?php

namespace Ivy\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestNormalizer implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): ?Response
    {
        $data = $request->request->all();
        $query = $request->query->all();

        array_walk_recursive($data, [$this, 'sanitize']);
        array_walk_recursive($query, [$this, 'sanitize']);

        $request->request->replace($data);
        $request->query->replace($query);

        return $next($request);
    }

    private function sanitize(&$value): void
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                $value = null;
            }
        }
    }
}
