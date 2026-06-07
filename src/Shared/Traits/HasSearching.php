<?php

namespace Ivy\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Container\Container;
use Ivy\Shared\Infrastructure\Service\SearchService;

trait HasSearching
{
    public function scopeSearch(
        Builder $query,
        Request $request,
    ): Builder {

        $columns = static::$searchable ?? [];

        $term = trim((string) (
            $request->query->get('search') ?? $request->request->get('search') ?? ''
        ));

        if ($term === '' || empty($columns)) {
            return $query;
        }

        return Container::getInstance()->get(SearchService::class)->apply($query, $term, $columns);
    }
}