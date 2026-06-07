<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;

class SortService
{
    public function __construct(
        private RelationPathService $relationPathService
    ) {}

    public function apply(
        Builder $query,
        string $column,
        array $sortable,
        string $defaultColumn = 'id',
        string $direction = 'asc'
    ): Builder {

        if (!in_array($column, $sortable, true)) {
            $column = $defaultColumn;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $query->reorder();

        $model = $query->getModel();
        $baseTable = $model->getTable();

        if (!str_contains($column, '.')) {
            return $query
                ->orderBy("$baseTable.$column", $direction)
                ->addSelect("$baseTable.*");
        }

        $resolved = $this->relationPathService->resolve($model, $column, $query);

        return $query
            ->orderBy("{$resolved['table']}.{$resolved['field']}", $direction)
            ->addSelect("$baseTable.*");
    }
}