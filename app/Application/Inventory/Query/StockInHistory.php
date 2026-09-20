<?php

namespace App\Application\Inventory\Query;

interface StockInHistory
{
    /** @return list<array{id: int, name: string, qty: int, time: string}> */
    public function latest(int $limit = 30): array;
}
