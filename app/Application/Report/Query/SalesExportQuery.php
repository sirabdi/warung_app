<?php

namespace App\Application\Report\Query;

use DateTimeImmutable;

/**
 * Everything behind the report's Excel export, unpaginated. The page shows the
 * day one page at a time; the file carries all of it.
 *
 * Quantities and stock are in steps of each product's unit (pieces, grams or
 * ml). Prices and cost were stored at sale time; the product's name and
 * category are today's, because a sale only keeps the product id.
 */
interface SalesExportQuery
{
    /**
     * Every line sold on $date, oldest first.
     *
     * @return list<array{code: string, sold_at: DateTimeImmutable, product_id: int, product: string, category: ?string, unit: string, qty: int, price: int, total: int, cost_total: int, cashier: ?string}>
     */
    public function lines(DateTimeImmutable $date): array;

    /**
     * Every product at or below $threshold (in display units), emptiest first.
     *
     * @return list<array{name: string, category: ?string, unit: string, stock: int}>
     */
    public function lowStock(int $threshold): array;
}
