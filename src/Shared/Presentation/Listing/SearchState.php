<?php

namespace Ivy\Shared\Presentation\Listing;

class SearchState
{
    public function __construct(
        public readonly string $search,
    ) {}
}
