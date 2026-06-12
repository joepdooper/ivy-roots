<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function apply(
        Builder $query,
        string $term,
        array $columns
    ): Builder {

        if ($term === '' || empty($columns)) {
            return $query;
        }

        $model = $query->getModel();
        $baseTable = $model->getTable();

        $query->where(function (Builder $q) use ($baseTable, $term, $columns) {

            $isFirst = true;

            foreach ($columns as $column) {

                if (!str_contains($column, '.')) {

                    if ($isFirst) {
                        $q->where(
                            "$baseTable.$column",
                            'LIKE',
                            "%{$term}%"
                        );
                    } else {
                        $q->orWhere(
                            "$baseTable.$column",
                            'LIKE',
                            "%{$term}%"
                        );
                    }

                    $isFirst = false;
                    continue;
                }

                $this->applyRelationSearch($q, $column, $term, $isFirst);

                $isFirst = false;
            }
        });

        return $query;
    }

    protected function applyRelationSearch(
        Builder $query,
        string $path,
        string $term,
        bool $useWhereInsteadOfOr = false
    ): void {

        $segments = explode('.', $path);
        $field = array_pop($segments);
        $relationPath = implode('.', $segments);

        $method = $useWhereInsteadOfOr ? 'whereHas' : 'orWhereHas';

        $query->{$method}($relationPath, function (Builder $q) use ($field, $term) {
            $q->where($field, 'LIKE', "%{$term}%");
        });
    }
}