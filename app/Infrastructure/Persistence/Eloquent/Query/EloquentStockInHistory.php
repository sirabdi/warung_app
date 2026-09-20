<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Inventory\Query\StockInHistory;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn;

final class EloquentStockInHistory implements StockInHistory
{
    public function latest(int $limit = 30): array
    {
        return StockIn::with('product:id,name')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (StockIn $stockIn) => [
                'id' => $stockIn->id,
                'name' => $stockIn->product->name,
                'qty' => $stockIn->qty,
                'time' => $stockIn->created_at->format('d/m H:i'),
            ])
            ->all();
    }
}
