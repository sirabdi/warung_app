<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Sale\Entity\Sale;
use App\Domain\Sale\Entity\SaleItem;
use App\Domain\Sale\Repository\SaleRepository;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;

final class EloquentSaleRepository implements SaleRepository
{
    /** One sale = several transactions rows sharing the same code. */
    public function save(Sale $sale): void
    {
        foreach ($sale->items() as $item) {
            /** @var SaleItem $item */
            Transaction::create([
                'code' => (string) $sale->code(),
                'product_id' => $item->productId,
                'qty' => $item->qty,
                'price' => $item->price->amount,
                'cost_price' => $item->costPrice->amount,
                'cost_total' => $item->costTotal()->amount,
                'total' => $item->total()->amount,
                'sold_at' => $sale->soldAt(),
                'user_id' => $sale->cashierId(),
            ]);
        }
    }
}
