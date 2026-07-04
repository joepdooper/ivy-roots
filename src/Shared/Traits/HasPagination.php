<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Infrastructure\Service\PaginationService;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

/** @phpstan-ignore-next-line trait.unused */
trait HasPagination
{
    /**
     * @throws CircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     */
    public function scopePages(
        Builder $query,
        Request $request,
    ): Builder {
        return Container::getInstance()->get(PaginationService::class)->apply($query, $request);
    }
}