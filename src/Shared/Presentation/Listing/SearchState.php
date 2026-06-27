<?php

namespace Ivy\Shared\Presentation\Listing;

use Illuminate\Support\Collection;

class SearchState
{
    public function __construct(
        public readonly string $search,
    ) {}
}