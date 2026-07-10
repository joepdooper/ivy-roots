<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Infrastructure\Service\SortService;
use Symfony\Component\HttpFoundation\Request;

/** @phpstan-ignore-next-line trait.unused */
trait HasSorting
{
    public function scopeSort(
        Builder $query,
        Request $request,
        string $defaultColumn = 'id',
        string $defaultDirection = 'asc'
    ): Builder {

        $columns = static::$sortable ?? ['id'];

        $column = $request->query->get('sort', $defaultColumn);
        $direction = strtolower(
            $request->query->get('direction', $defaultDirection)
        );

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return Container::getInstance()->get(SortService::class)->apply(
            $query,
            $column,
            $columns,
            $defaultColumn,
            $direction
        );
    }
}
