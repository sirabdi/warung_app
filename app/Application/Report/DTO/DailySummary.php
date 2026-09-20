<?php

namespace App\Application\Report\DTO;

use App\Domain\Shared\ValueObject\Money;

final readonly class DailySummary
{
    public function __construct(
        public Money $revenue,
        public Money $profit,
        public int $items,
        public int $sales,
    ) {}

    /** @return array{revenue: int, profit: int, items: int, sales: int} */
    public function toArray(): array
    {
        return [
            'revenue' => $this->revenue->amount,
            'profit' => $this->profit->amount,
            'items' => $this->items,
            'sales' => $this->sales,
        ];
    }
}
