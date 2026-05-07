<?php

namespace Ivy\Presentation\Middleware;

use Symfony\Component\HttpFoundation\Request;

class RequestNormalizer implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $data = $request->request->all();
        $query = $request->query->all();

        array_walk_recursive($data, [$this, 'sanitize']);
        array_walk_recursive($query, [$this, 'sanitize']);

        $request->request->replace($data);
        $request->query->replace($query);
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
