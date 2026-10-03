<?php

namespace App\Application\Report\DTO;

use App\Application\Shared\Query\Page;

/**
 * Contents of the daily dashboard: revenue summary, best sellers, low stock,
 * and that day's sale history.
 */
final readonly class DailyReport
{
    /**
     * Quantities are in steps of each product's unit (pieces, grams or ml).
     *
     * @param  list<array{name: string, unit: string, qty: int, revenue: int}>  $bestSellers
     * @param  Page<array{id: int, name: string, unit: string, stock: int}>  $lowStock
     * @param  Page<array{code: string, time: string, items: int, total: int}>  $history
     */
    public function __construct(
        public DailySummary $summary,
        public array $bestSellers,
        public Page $lowStock,
        public Page $history,
    ) {}
}
