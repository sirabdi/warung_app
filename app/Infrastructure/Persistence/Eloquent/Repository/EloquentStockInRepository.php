<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Inventory\Entity\StockIn;
use App\Domain\Inventory\Repository\StockInRepository;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn as StockInModel;

final class EloquentStockInRepository implements StockInRepository
{
    public function save(StockIn $stockIn): void
    {
        StockInModel::create([
            'product_id' => $stockIn->productId,
            'qty' => $stockIn->qty,
            'date' => $stockIn->date,
            'user_id' => $stockIn->recordedBy,
        ]);
    }
}
