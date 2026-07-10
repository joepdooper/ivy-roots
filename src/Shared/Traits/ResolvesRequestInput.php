<?php

namespace Ivy\Shared\Traits;

use Symfony\Component\HttpFoundation\Request;

trait ResolvesRequestInput
{
    protected function input(
        Request $request,
        string $key,
        mixed $default = null
    ): mixed {
        return $request->query->get($key)
            ?? $request->request->get($key)
            ?? $default;
    }

    protected function string(
        Request $request,
        string $key,
        string $default = ''
    ): string {
        return trim((string) $this->input(
            $request,
            $key,
            $default
        ));
    }

    protected function int(
        Request $request,
        string $key,
        int $default = 0
    ): int {
        return (int) $this->input(
            $request,
            $key,
            $default
        );
    }

    protected function bool(
        Request $request,
        string $key,
        bool $default = false
    ): bool {
        return filter_var(
            $this->input($request, $key, $default),
            FILTER_VALIDATE_BOOL
        );
    }
}
