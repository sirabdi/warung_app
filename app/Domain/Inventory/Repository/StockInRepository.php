<?php

namespace App\Domain\Inventory\Repository;

use App\Domain\Inventory\Entity\StockIn;

interface StockInRepository
{
    public function save(StockIn $stockIn): void;
}
