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

        return Container::getInstance()->get(SortService::class)->apply(
            $query,
            $request,
            $columns,
            $defaultColumn,
            $defaultDirection
        );
    }
}
