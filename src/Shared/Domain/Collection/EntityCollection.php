<?php

namespace Ivy\Shared\Domain\Collection;

use Illuminate\Database\Eloquent\Collection;
use Ivy\Shared\Domain\Data\PaginationResult;

class EntityCollection extends Collection
{
    protected ?PaginationResult $pagination = null;

    public function setPagination(PaginationResult $pagination): static
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function pagination(): ?PaginationResult
    {
        return $this->pagination;
    }
}