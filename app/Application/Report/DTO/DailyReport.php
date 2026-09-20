<?php

namespace App\Application\Report\DTO;

/**
 * Contents of the daily dashboard: revenue summary, best sellers, low stock,
 * and that day's sale history.
 */
final readonly class DailyReport
{
    /**
     * @param  list<array{name: string, qty: int, revenue: int}>  $bestSellers
     * @param  list<array{id: int, name: string, stock: int}>  $lowStock
     * @param  list<array{code: string, time: string, items: int, total: int}>  $history
     */
    public function __construct(
        public DailySummary $summary,
        public array $bestSellers,
        public array $lowStock,
        public array $history,
    ) {}
}
