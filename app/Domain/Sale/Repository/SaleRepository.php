<?php

namespace App\Domain\Sale\Repository;

use App\Domain\Sale\Entity\Sale;

interface SaleRepository
{
    public function save(Sale $sale): void;
}
