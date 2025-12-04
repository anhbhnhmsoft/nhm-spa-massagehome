<?php

namespace App\Core\Controller;

class FilterDTO
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $sortBy,
        public readonly string $direction,

        // Bỏ readonly ở đây để có thể sửa được
        public array $filters
    ) {
    }

    // Bạn có thể thêm setter nếu muốn (hoặc gán trực tiếp $dto->filters = ...)
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    // Hoặc hàm merge thêm filter mới
    public function addFilter(string $key, mixed $value): void
    {
        $this->filters[$key] = $value;
    }
}
