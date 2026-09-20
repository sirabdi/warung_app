<?php

namespace Tests\Support;

use App\Domain\Sale\Entity\Sale;
use App\Domain\Sale\Repository\SaleRepository;

final class InMemorySaleRepository implements SaleRepository
{
    /** @var list<Sale> */
    public array $saved = [];

    public function save(Sale $sale): void
    {
        $this->saved[] = $sale;
    }
}
