<?php

namespace App\Application\Shared\Query;

/**
 * One page of a read-model list. A plain object on purpose: the application
 * layer stays free of Laravel's paginator.
 *
 * @template T
 */
final readonly class Page
{
    /** @param list<T> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    /** @return array{data: list<T>, meta: array{page: int, per_page: int, total: int, last_page: int}} */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'meta' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'last_page' => $this->lastPage(),
            ],
        ];
    }
}
