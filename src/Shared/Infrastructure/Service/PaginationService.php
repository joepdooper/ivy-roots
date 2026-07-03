<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    use ResolvesRequestInput;

    /**
     * @param EntityBuilder<Entity> $query
     * @return EntityBuilder<Entity>
     */
    public function apply(
        EntityBuilder $query,
        Request $request,
        int $defaultPerPage = 25
    ): EntityBuilder {

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

        /** @var EntityBuilder<Entity> */
        return $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage);
    }
}