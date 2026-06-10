<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Domain\Data\PaginationResult;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public function apply(
        Builder $query,
        Request $request,
        int $perPage = 25
    ): Builder {

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, (int) $request->query->get('per_page', $perPage));

        $total = (clone $query)->count();

        $modelClass = get_class($query->getModel());

        $modelClass::setPagination(
            new PaginationResult(
                currentPage: $page,
                perPage: $perPage,
                total: $total,
                lastPage: (int) ceil($total / $perPage),
            )
        );

        return $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage);
    }
}