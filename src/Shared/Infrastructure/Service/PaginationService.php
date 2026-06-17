<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Domain\Data\PaginationResult;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    use ResolvesRequestInput;

    public function apply(
        Builder $query,
        Request $request,
        int $defaultPerPage = 25
    ): Builder {

        $page = max(1, $this->int($request, 'page', 1));

        $perPage = max(1, $this->int($request, 'per_page', $defaultPerPage));

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