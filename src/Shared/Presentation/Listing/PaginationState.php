<?php

namespace Ivy\Shared\Presentation\Listing;

class PaginationState
{
    public function __construct(
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
        /** @var int[] */
        public array $perPageOptions = [5, 10, 25, 50, 100],
    ) {}

    /** @return int[] */
    public function pages(): array
    {
        return range(1, $this->lastPage);
    }

    public function from(): int
    {
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }
}
