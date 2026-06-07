<?php

namespace Ivy\Shared\Infrastructure\Service;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public function paginate(
        Builder $query,
        Request $request,
        int $perPage = 25
    ): array {

        $page = max(1, (int) $request->query->get('page', 1));

        $total = (clone $query)->count();

        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_next' => $page < $lastPage,
                'has_prev' => $page > 1,
            ],
        ];
    }
}