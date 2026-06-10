<?php

namespace Ivy\Shared\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Domain\Data\PaginationResult;
use Ivy\Shared\Infrastructure\Service\PaginationService;
use Symfony\Component\HttpFoundation\Request;

trait HasPagination
{
    protected static ?PaginationResult $pagination = null;

    public static function pagination(): ?PaginationResult
    {
        return static::$pagination;
    }

    public static function setPagination(PaginationResult $pagination): void
    {
        static::$pagination = $pagination;
    }

    public function scopePaged(
        Builder $query,
        Request $request,
    ): Builder {

        return Container::getInstance()->get(PaginationService::class)->apply($query, $request);
    }
}