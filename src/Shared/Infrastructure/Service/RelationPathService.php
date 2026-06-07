<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;

class RelationPathService
{
    public function resolve(object $model, string $path, Builder $query): array
    {
        $segments = explode('.', $path);
        $field = array_pop($segments);

        $parent = $model;
        $parentTable = $model->getTable();

        foreach ($segments as $relationName) {

            $relation = $parent->{$relationName}();
            $related = $relation->getRelated();
            $relatedTable = $related->getTable();

            $query->leftJoin(
                $relatedTable,
                $relation->getQualifiedParentKeyName(),
                '=',
                $relation->getQualifiedForeignKeyName()
            );

            $parent = $related;
            $parentTable = $relatedTable;
        }

        return [
            'table' => $parentTable,
            'field' => $field,
        ];
    }
}