<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Report\Query\SalesExportQuery;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class EloquentSalesExportQuery implements SalesExportQuery
{
    use BuildsUnitSql;

    public function lines(DateTimeImmutable $date): array
    {
        $day = Carbon::instance($date);

        return Transaction::whereBetween('sold_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('users', 'users.id', '=', 'transactions.user_id')
            ->orderBy('transactions.sold_at')
            ->orderBy('transactions.id')
            ->get([
                'transactions.code', 'transactions.sold_at', 'transactions.product_id', 'transactions.qty',
                'transactions.price', 'transactions.total', 'transactions.cost_total',
                'products.name as product', 'products.unit', 'categories.name as category', 'users.name as cashier',
            ])
            ->map(fn (Transaction $line) => [
                'code' => $line->code,
                'sold_at' => $line->sold_at->toDateTimeImmutable(),
                'product_id' => $line->product_id,
                'product' => $line->product,
                'category' => $line->category,
                'unit' => $line->unit,
                'qty' => $line->qty,
                'price' => $line->price,
                'total' => $line->total,
                'cost_total' => $line->cost_total,
                'cashier' => $line->cashier,
            ])
            ->all();
    }

    public function lowStock(int $threshold): array
    {
        return Product::whereRaw($this->lowStockSql($threshold))
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->orderByRaw($this->stockInDisplayUnitsSql())
            ->orderBy('products.name')
            ->orderBy('products.id')
            ->get(['products.name', 'products.unit', 'products.stock', 'categories.name as category'])
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'category' => $product->category,
                'unit' => $product->unit,
                'stock' => $product->stock,
            ])
            ->all();
    }
}
