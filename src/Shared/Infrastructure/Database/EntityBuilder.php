<?php

namespace Ivy\Shared\Infrastructure\Database;

use Illuminate\Database\Eloquent\Builder;
use Ivy\Shared\Domain\Collection\EntityCollection;
use Ivy\Shared\Presentation\Listing\PaginationState;
use Ivy\Shared\Presentation\Listing\SearchState;

class EntityBuilder extends Builder
{
    protected ?PaginationState $paginationState = null;

    protected ?SearchState $searchState = null;

    public function setPaginationState(PaginationState $state): static
    {
        $this->paginationState = $state;

        return $this;
    }

    public function paginationState(): ?PaginationState
    {
        return $this->paginationState;
    }

    public function setSearchState(SearchState $state): static
    {
        $this->searchState = $state;

        return $this;
    }

    public function searchState(): ?SearchState
    {
        return $this->searchState;
    }

    public function get($columns = ['*']): EntityCollection
    {
        /** @var EntityCollection $collection */
        $collection = parent::get($columns);

        if ($this->paginationState !== null) {
            $collection->setPaginationState($this->paginationState);
        }

        if ($this->searchState !== null) {
            $collection->setSearchState($this->searchState);
        }

        return $collection;
    }
}