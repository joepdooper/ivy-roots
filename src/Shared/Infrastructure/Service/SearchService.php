<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;
use Ivy\Shared\Presentation\Listing\SearchState;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class SearchService
{
    use ResolvesRequestInput;

    /**
     * @param  EntityBuilder<Entity>  $query
     * @param  array<int, string>  $columns
     * @return EntityBuilder<Entity>
     */
    public function apply(
        EntityBuilder $query,
        Request $request,
        array $columns = []
    ): EntityBuilder {

        $term = $this->string($request, 'search');

        if ($term === '' || empty($columns)) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        $query->where(function (Builder $q) use ($table, $term, $columns) {

            foreach ($columns as $column) {

                if (! str_contains($column, '.')) {
                    $q->orWhere(
                        "{$table}.{$column}",
                        'LIKE',
                        "%{$term}%"
                    );

                    continue;
                }

                /** @var EntityBuilder<Entity> $q */
                $this->applyRelationSearch($q, $column, $term);
            }
        });

        $state = new SearchState($term);

        $query->setSearchState($state);

        return $query;
    }

    /**
     * @param  EntityBuilder<Entity>  $query
     */
    protected function applyRelationSearch(
        EntityBuilder $query,
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
