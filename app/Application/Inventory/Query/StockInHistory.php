<?php

namespace App\Application\Inventory\Query;

use App\Application\Shared\Query\Page;

interface StockInHistory
{
    /**
     * Stock-in records, newest first.
     *
     * @return Page<array{id: int, name: string, unit: string, qty: int, time: string}>
     */
    public function paginate(int $page, int $perPage): Page;
}
