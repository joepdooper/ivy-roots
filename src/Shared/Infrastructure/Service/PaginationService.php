<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Presentation\Listing\PaginationState;
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

        $state = new PaginationState(
            currentPage: $page,
            perPage: $perPage,
            total: $total,
            lastPage: (int) ceil($total / $perPage),
        );

        $query->setPaginationState($state);

        return $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage);
    }
}