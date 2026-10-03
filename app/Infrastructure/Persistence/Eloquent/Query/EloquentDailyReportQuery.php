<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Report\DTO\DailyReport;
use App\Application\Report\DTO\DailySummary;
use App\Application\Report\Query\DailyReportQuery;
use App\Application\Shared\Query\Page;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentDailyReportQuery implements DailyReportQuery
{
    use PaginatesQueries;

    public function for(
        DateTimeImmutable $date,
        int $lowStockThreshold,
        int $lowStockPage = 1,
        int $historyPage = 1,
        int $perPage = 10,
    ): DailyReport {
        $day = Carbon::instance($date);
        $range = [$day->copy()->startOfDay(), $day->copy()->endOfDay()];

        return new DailyReport(
            $this->summary($range),
            $this->bestSellers($range),
            $this->lowStock($lowStockThreshold, $lowStockPage, $perPage),
            $this->history($range, $historyPage, $perPage),
        );
    }

    /** @param array{Carbon, Carbon} $range */
    private function summary(array $range): DailySummary
    {
        $row = Transaction::whereBetween('sold_at', $range)
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->selectRaw('COALESCE(SUM(transactions.total), 0) as revenue')
            // Gross profit uses the cost at sale time, not today's.
            ->selectRaw('COALESCE(SUM(transactions.total - transactions.cost_total), 0) as profit')
            ->selectRaw('COALESCE(SUM('.$this->itemCountSql().'), 0) as items')
            ->selectRaw('COUNT(DISTINCT transactions.code) as sales')
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
            ->groupBy('transactions.product_id', 'products.name', 'products.unit')
            ->select('products.name', 'products.unit', DB::raw('SUM(transactions.qty) as qty'), DB::raw('SUM(transactions.total) as revenue'))
            // By revenue: 3 pcs and 1,5 kg cannot be compared by quantity.
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'unit' => $row->unit,
                'qty' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    private function lowStock(int $threshold, int $page, int $perPage): Page
    {
        // The threshold is in display units: 5 pcs, or 5 kg = 5000 gram.
        $limit = $this->perUnitSql(fn (Unit $unit) => $threshold * $unit->scale());

        // Emptiest first, compared in display units (1,4 kg before 2 pcs).
        $scale = $this->perUnitSql(fn (Unit $unit) => $unit->scale());

        $query = Product::whereRaw("stock <= {$limit}")
            ->orderByRaw("stock / {$scale}")
            ->orderBy('name')
            ->orderBy('id');

        return $this->page($query, $page, $perPage, ['id', 'name', 'unit', 'stock'], fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'unit' => $product->unit,
            'stock' => $product->stock,
        ]);
    }

    /** @param array{Carbon, Carbon} $range */
    private function history(array $range, int $page, int $perPage): Page
    {
        $query = Transaction::whereBetween('sold_at', $range)
            ->join('products', 'products.id', '=', 'transactions.product_id')
            ->groupBy('transactions.code')
            ->select(
                'transactions.code',
                DB::raw('MIN(transactions.sold_at) as sold_at'),
                DB::raw('SUM('.$this->itemCountSql().') as items'),
                DB::raw('SUM(transactions.total) as total'),
            )
            ->orderByDesc('sold_at')
            ->orderByDesc('transactions.code');

        return $this->page($query, $page, $perPage, ['*'], fn ($row) => [
            'code' => $row->code,
            'time' => Carbon::parse($row->sold_at)->format('H:i'),
            'items' => (int) $row->items,
            'total' => (int) $row->total,
        ]);
    }

    /** Same rule as Unit::itemCount(): pieces one by one, a weighed line as one. */
    private function itemCountSql(): string
    {
        return $this->perUnitSql(fn (Unit $unit) => $unit->isMeasured() ? '1' : 'transactions.qty');
    }

    /** CASE products.unit WHEN 'pcs' THEN … END, built from the Unit enum. */
    private function perUnitSql(callable $expression): string
    {
        $cases = array_map(
            fn (Unit $unit) => "WHEN '{$unit->value}' THEN ".$expression($unit),
            Unit::cases(),
        );

        return 'CASE products.unit '.implode(' ', $cases).' END';
    }
}
