<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Inventory\Query\StockInHistory;
use App\Application\Shared\Query\Page;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn;

final class EloquentStockInHistory implements StockInHistory
{
    use PaginatesQueries;

    public function paginate(int $page, int $perPage): Page
    {
        $query = StockIn::with('product:id,name,unit')->latest('id');

        return $this->page($query, $page, $perPage, ['*'], fn (StockIn $stockIn) => [
            'id' => $stockIn->id,
            'name' => $stockIn->product->name,
            'unit' => $stockIn->product->unit,
            'qty' => $stockIn->qty,
            'time' => $stockIn->created_at->format('d/m H:i'),
        ]);
    }
}
