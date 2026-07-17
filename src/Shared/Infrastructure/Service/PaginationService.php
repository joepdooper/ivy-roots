<?php

namespace Ivy\Shared\Infrastructure\Service;

use Ivy\Shared\Base\Entity;
use Ivy\Shared\Infrastructure\Database\EntityBuilder;
use Ivy\Shared\Infrastructure\Manager\SessionManager;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Ivy\Shared\Traits\ResolvesRequestInput;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    use ResolvesRequestInput;

    private const SESSION_KEY = 'pagination.per_page';

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

        $perPage = $this->resolvePerPage($request, $defaultPerPage);

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

    private function resolvePerPage(
        Request $request,
        int $default
    ): int {
        $perPage = $request->query->has('per_page')
            ? $request->query->getInt('per_page')
            : SessionManager::get('pagination.per_page', $default);

        $perPage = max(1, $perPage);

        SessionManager::set('pagination.per_page', $perPage);

        return $perPage;
    }
}