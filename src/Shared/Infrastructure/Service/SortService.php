<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;

readonly class SortService
{
    public function __construct(
        private RelationPathService $relationPathService
    ) {}

    /**
     * @param  EntityBuilder<Entity>  $query
     * @param  array<int, string>  $sortable
     * @return EntityBuilder<Entity>
     */
    public function apply(
        EntityBuilder $query,
        string $column,
        array $sortable,
        string $defaultColumn = 'id',
        string $direction = 'asc'
    ): EntityBuilder {

        if (! in_array($column, $sortable, true)) {
            $column = $defaultColumn;
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        /** @var EntityBuilder<Entity> $query */
        $query->reorder();

        /** @var Entity $model */
        $model = $query->getModel();
        $baseTable = $model->getTable();

        if (! str_contains($column, '.')) {
            /** @var EntityBuilder<Entity> */
            return $query
                ->orderBy("$baseTable.$column", $direction)
                ->addSelect("$baseTable.*");
        }

        $resolved = $this->relationPathService->resolve($model, $column, $query);

        /** @var EntityBuilder<Entity> */
        return $query
            ->orderBy("{$resolved['table']}.{$resolved['field']}", $direction)
            ->addSelect("$baseTable.*");
    }
}
