<?php

namespace Ivy\Shared\Domain\Collection;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Ivy\Shared\Presentation\Listing\SearchState;

/**
 * @template TModel of Model
 *
 * @extends Collection<int, TModel>
 */
class EntityCollection extends Collection
{
    protected ?PaginationState $paginationState = null;

    protected ?SearchState $searchState = null;

    public function setPaginationState(PaginationState $paginationState): static
    {
        $this->paginationState = $paginationState;

        return $this;
    }

    public function paginationState(): ?PaginationState
    {
        return $this->paginationState;
    }

    public function setSearchState(SearchState $searchState): static
    {
        $this->searchState = $searchState;

        return $this;
    }

    public function searchState(): ?SearchState
    {
        return $this->searchState;
    }
}
