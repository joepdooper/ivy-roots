<?php

namespace Ivy\Shared\Presentation\Routing;

final class QueryBuilder
{
    /**
     * @param array<string, int|string|bool|float|null> $current
     * @param array<string, int|string|bool|float|null> $replace
     * @param array<int, string> $remove
     */
    public function build(array $current, array $replace = [], array $remove = []): string
    {
        $query = $current;

        foreach ($replace as $key => $value) {
            $query[$key] = $value;
        }

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        return $query ? '?' . http_build_query($query) : '';
    }
}
