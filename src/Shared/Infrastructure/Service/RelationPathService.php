<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;

class RelationPathService
{
    /**
     * @param Entity $model
     * @param string $path
     * @param EntityBuilder<Entity> $query
     * @return array{table: string, field: string}
     */
    public function resolve(Entity $model, string $path, EntityBuilder $query): array
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