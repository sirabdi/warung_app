<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Report\DTO\DailyReport;
use App\Application\Report\DTO\DailySummary;
use App\Application\Report\Query\DailyReportQuery;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentDailyReportQuery implements DailyReportQuery
{
    public function for(DateTimeImmutable $date, int $lowStockThreshold): DailyReport
    {
        $day = Carbon::instance($date);
        $range = [$day->copy()->startOfDay(), $day->copy()->endOfDay()];

        return new DailyReport(
            $this->summary($range),
            $this->bestSellers($range),
            $this->lowStock($lowStockThreshold),
            $this->history($range),
        );
    }

    /** @param array{Carbon, Carbon} $range */
    private function summary(array $range): DailySummary
    {
        $row = Transaction::whereBetween('sold_at', $range)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            // Gross profit uses the cost price at sale time, not today's.
            ->selectRaw('COALESCE(SUM(total - cost_price * qty), 0) as profit')
            ->selectRaw('COALESCE(SUM(qty), 0) as items')
            ->selectRaw('COUNT(DISTINCT code) as sales')
            ->first();

        return new DailySummary(
            Money::of((int) $row->revenue),
            Money::signed((int) $row->profit),
            (int) $row->items,
            (int) $row->sales,
        );
    }

    /** @param array{Carbon, Carbon} $range */
    private function bestSellers(array $range): array
    {
        return Transaction::whereBetween('sold_at', $range)
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->groupBy('transactions.product_id', 'products.name')
            ->select('products.name', DB::raw('SUM(transactions.qty) as qty'), DB::raw('SUM(transactions.total) as revenue'))
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'qty' => (int) $row->qty, 'revenue' => (int) $row->revenue])
            ->all();
    }

    private function lowStock(int $threshold): array
    {
        return Product::where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->get(['id', 'name', 'stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
            ])
            ->all();
    }

    /** @param array{Carbon, Carbon} $range */
    private function history(array $range): array
    {
        return Transaction::whereBetween('sold_at', $range)
            ->groupBy('code')
            ->select('code', DB::raw('MIN(sold_at) as sold_at'), DB::raw('SUM(qty) as items'), DB::raw('SUM(total) as total'))
            ->orderByDesc('sold_at')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'time' => Carbon::parse($row->sold_at)->format('H:i'),
                'items' => (int) $row->items,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}
