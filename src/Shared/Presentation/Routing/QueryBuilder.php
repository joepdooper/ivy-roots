<?php

namespace Ivy\Shared\Presentation\Routing;

final class QueryBuilder
{
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
