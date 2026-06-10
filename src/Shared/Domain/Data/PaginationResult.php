<?php

namespace Ivy\Shared\Domain\Data;

use Illuminate\Support\Collection;

class PaginationResult
{
    public function __construct(
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
        public array $perPageOptions = [5, 10, 25, 50, 100],
    ) {}

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