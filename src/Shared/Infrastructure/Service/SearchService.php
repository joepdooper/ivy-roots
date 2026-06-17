<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class SearchService
{
    use ResolvesRequestInput;

    public function apply(
        Builder $query,
        Request $request,
        array $columns = []
    ): Builder {

        $term = $this->string($request, 'search');

        if ($term === '' || empty($columns)) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        $query->where(function (Builder $q) use ($table, $term, $columns) {

            foreach ($columns as $column) {

                if (!str_contains($column, '.')) {
                    $q->orWhere(
                        "{$table}.{$column}",
                        'LIKE',
                        "%{$term}%"
                    );

                    continue;
                }

                $this->applyRelationSearch($q, $column, $term);
            }
        });

        return $query;
    }

    protected function applyRelationSearch(
        Builder $query,
        string $path,
        string $term
    ): void {

        $segments = explode('.', $path);
        $field = array_pop($segments);
        $relation = implode('.', $segments);

        $query->orWhereHas($relation, function (Builder $q) use ($field, $term) {
            $q->where($field, 'LIKE', "%{$term}%");
        });
    }
}