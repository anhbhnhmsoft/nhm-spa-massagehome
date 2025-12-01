<?php

namespace App\Core\Controller;

readonly class FilterDTO
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $sortBy,
        public string $direction,
        public array $filters
    ) {
    }
}
