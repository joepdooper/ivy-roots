<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class SortService
{
    use ResolvesRequestInput;

    public function __construct(
        private RelationPathService $relationPathService
    ) {}

    /**
     * @param EntityBuilder<Entity> $query
     * @param Request $request
     * @param string[] $columns
     * @param string $defaultColumn
     * @param string $defaultDirection
     * @return EntityBuilder<Entity>
     */
    public function apply(
        EntityBuilder $query,
        Request $request,
        array $columns = [],
        string $defaultColumn = 'id',
        string $defaultDirection = 'asc'
    ): EntityBuilder {

        $column = $this->string($request, 'sort', $defaultColumn);
        $direction = strtolower($this->string($request, 'direction', $defaultDirection));

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        if (! in_array($column, $columns, true)) {
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
