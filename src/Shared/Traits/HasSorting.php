<?php

namespace Ivy\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Request;

trait HasSorting
{
    public function scopeSort(
        Builder $query,
        Request $request,
        string $defaultColumn = 'id',
        string $defaultDirection = 'asc'
    ): Builder {
        $columns = static::$sortable ?? ['id'];

        if (!in_array($defaultColumn, $columns, true) && $defaultColumn !== 'id') {
            throw new \InvalidArgumentException(
                "Invalid default sort column [$defaultColumn]"
            );
        }

        $column = $request->query->get('sort', $defaultColumn);
        $direction = strtolower(
            $request->query->get('direction', $defaultDirection)
        );

        if (!in_array($column, $columns, true)) {
            $column = $defaultColumn;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return $query
            ->reorder()
            ->orderBy($column, $direction);
    }
}