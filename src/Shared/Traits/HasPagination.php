<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Infrastructure\Service\PaginationService;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Symfony\Component\HttpFoundation\Request;

trait HasPagination
{
    public function scopePages(
        Builder $query,
        Request $request,
    ): Builder {
        return Container::getInstance()->get(PaginationService::class)->apply($query, $request);
    }
}