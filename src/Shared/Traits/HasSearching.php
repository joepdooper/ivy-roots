<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Container\Container;
use Ivy\Shared\Infrastructure\Service\SearchService;

/** @phpstan-ignore-next-line trait.unused */
trait HasSearching
{
    /**
     * @throws CircularDependencyException
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function scopeSearch(
        Builder $query,
        Request $request,
    ): Builder {
        $columns = static::$searchable ?? [];

        return Container::getInstance()->get(SearchService::class)->apply($query, $request, $columns);
    }
}