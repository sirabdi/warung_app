<?php

namespace App\Infrastructure\Export;

use App\Domain\Shared\ValueObject\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The report page as an .xlsx: a summary plus one sheet per angle on the day.
 *
 * Amounts are real numbers with a rupiah format, so they can be summed,
 * filtered and pivoted in Excel. Quantities are in display units (1,52 kg,
 * not 1520 gram), with the unit in its own column.
 *
 * @phpstan-type Line array{code: string, sold_at: \DateTimeImmutable, product_id: int, product: string, category: ?string, unit: string, qty: int, price: int, total: int, cost_total: int, cashier: ?string}
 */
final class DailyReportWorkbook
{
    private const RUPIAH = '"Rp" #,##0;-"Rp" #,##0';

    private const NO_CATEGORY = 'Tanpa kategori';

    /** Rows above each sheet's table: store name, address, what and when. */
    private const LETTERHEAD_ROWS = 4;

    private Style $title;

    private Style $muted;

    private Style $header;

    private Style $label;

    private Style $money;

    private Style $percent;

    private Style $totalLabel;

    private Style $totalMoney;

    private Style $totalNumber;

    /** @var Collection<int, Line> */
    private Collection $lines;

    /**
     * @param  list<Line>  $lines
     * @param  list<array{name: string, category: ?string, unit: string, stock: int}>  $lowStock
     */
    public function __construct(
        private readonly ?string $storeName,
        private readonly ?string $storeAddress,
        private readonly CarbonImmutable $date,
        private readonly CarbonImmutable $generatedAt,
        array $lines,
        private readonly array $lowStock,
        private readonly int $lowStockThreshold,
    ) {
        $this->lines = collect($lines);

        $line = new Border(new BorderPart(BorderName::BOTTOM));
        $this->title = new Style(fontBold: true, fontSize: 14);
        $this->muted = new Style(fontItalic: true, fontColor: '6B7280');
        $this->header = new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: '047857', border: $line);
        $this->label = new Style(fontBold: true);
        $this->money = new Style(format: self::RUPIAH);
        $this->percent = new Style(format: '0.0%');
        $this->totalLabel = new Style(fontBold: true, backgroundColor: 'ECFDF5');
        $this->totalMoney = new Style(fontBold: true, backgroundColor: 'ECFDF5', format: self::RUPIAH);
        $this->totalNumber = new Style(fontBold: true, backgroundColor: 'ECFDF5');
    }

    /** Laporan-Warung-Bu-Sri-2026-10-03.xlsx */
    public function fileName(): string
    {
        return 'Laporan-'.(Str::slug((string) $this->storeName) ?: 'toko').'-'.$this->date->toDateString().'.xlsx';
    }

    public function saveTo(string $path): void
    {
        $writer = new Writer;
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Ringkasan');
        $this->letterhead($writer, 'Ringkasan');
        $this->summary($writer);

        $this->sheet($writer, 'Transaksi', fn () => $this->sales($writer));
        $this->sheet($writer, 'Rincian item', fn () => $this->items($writer));
        $this->sheet($writer, 'Per produk', fn () => $this->products($writer));
        $this->sheet($writer, 'Per kategori', fn () => $this->categories($writer));
        $this->sheet($writer, 'Stok menipis', fn () => $this->lowStockSheet($writer));

        $writer->close();
    }

    private function sheet(Writer $writer, string $name, callable $fill): void
    {
        $writer->addNewSheetAndMakeItCurrent()->setName($name);
        $this->letterhead($writer, $name);
        $fill();
    }

    /** Which store and day the sheet is about, also when printed on its own. */
    private function letterhead(Writer $writer, string $sheetName): void
    {
        $writer->addRow(new Row([Cell::fromValue($this->orDash($this->storeName), $this->title)]));
        $writer->addRow(new Row([Cell::fromValue($this->orDash($this->storeAddress))]));
        $writer->addRow(new Row([Cell::fromValue(
            "Laporan penjualan harian · {$sheetName} · {$this->longDate($this->date)}",
            $this->muted,
        )]));
        $writer->addRow(new Row([]));
    }

    private function orDash(?string $value): string
    {
        return trim((string) $value) === '' ? '-' : trim($value);
    }

    private function summary(Writer $writer): void
    {
        $revenue = $this->lines->sum('total');
        $profit = $revenue - $this->lines->sum('cost_total');
        $sales = $this->lines->pluck('code')->unique()->count();

        $rows = [
            ['Tanggal laporan', $this->longDate($this->date)],
            ['Dibuat', $this->generatedAt->format('d/m/Y H:i')],
            [],
            ['Penjualan', Cell::fromValue($revenue, $this->money)],
            ['Laba kotor', Cell::fromValue($profit, $this->money)],
            ['Margin laba', Cell::fromValue($revenue > 0 ? $profit / $revenue : 0, $this->percent)],
            ['Jumlah transaksi', $sales],
            ['Item terjual', $this->itemCount($this->lines)],
            ['Rata-rata per transaksi', Cell::fromValue($sales > 0 ? (int) round($revenue / $sales) : 0, $this->money)],
            ['Produk berbeda terjual', $this->lines->pluck('product_id')->unique()->count()],
            ['Produk stok menipis', count($this->lowStock)],
        ];

        foreach ($rows as $row) {
            $writer->addRow(new Row(array_map(
                fn ($value, $index) => $value instanceof Cell ? $value : Cell::fromValue($value, $index === 0 ? $this->label : null),
                $row,
                array_keys($row),
            )));
        }
        $writer->addRow(new Row([]));
        $writer->addRow(new Row([Cell::fromValue('Laba kotor = penjualan − harga beli yang tercatat saat transaksi.', $this->muted)]));
        $writer->addRow(new Row([Cell::fromValue('Item terjual: barang satuan dihitung per buah, barang timbang per baris.', $this->muted)]));

        $writer->getCurrentSheet()->setColumnWidth(26, 1);
        $writer->getCurrentSheet()->setColumnWidth(32, 2);
    }

    /** One row per receipt. */
    private function sales(Writer $writer): void
    {
        $rows = $this->lines->groupBy('code')->map(fn (Collection $lines, string $code) => [
            $code,
            $lines->first()['sold_at']->format('H:i'),
            $lines->first()['cashier'] ?? '—',
            $lines->count(),
            $this->itemCount($lines),
            Cell::fromValue($lines->sum('total'), $this->money),
            Cell::fromValue($lines->sum('total') - $lines->sum('cost_total'), $this->money),
        ])->values();

        $this->table($writer, ['Kode', 'Jam', 'Kasir', 'Jenis produk', 'Item', 'Total', 'Laba kotor'], $rows, [
            'Total', '', '', '', $this->itemCount($this->lines),
            $this->lines->sum('total'), $this->lines->sum('total') - $this->lines->sum('cost_total'),
        ], [16, 8, 20, 13, 8, 16, 16]);
    }

    /** One row per product on a receipt: the sheet to pivot on. */
    private function items(Writer $writer): void
    {
        $rows = $this->lines->map(fn (array $line) => [
            $line['code'],
            $line['sold_at']->format('H:i'),
            $line['product'],
            $line['category'] ?? self::NO_CATEGORY,
            $this->displayQty($line['qty'], $line['unit']),
            $line['unit'],
            Cell::fromValue($line['price'], $this->money),
            Cell::fromValue($line['total'], $this->money),
            Cell::fromValue($line['cost_total'], $this->money),
            Cell::fromValue($line['total'] - $line['cost_total'], $this->money),
            $line['cashier'] ?? '—',
        ]);

        $this->table($writer, ['Kode', 'Jam', 'Produk', 'Kategori', 'Jumlah', 'Satuan', 'Harga jual', 'Subtotal', 'Modal', 'Laba kotor', 'Kasir'], $rows, [
            'Total', '', '', '', '', '', '',
            $this->lines->sum('total'), $this->lines->sum('cost_total'), $this->lines->sum('total') - $this->lines->sum('cost_total'), '',
        ], [16, 8, 28, 18, 10, 8, 14, 16, 16, 16, 20]);
    }

    /** Every product sold that day, by revenue: the page shows only the top 5. */
    private function products(Writer $writer): void
    {
        $revenue = max(1, $this->lines->sum('total'));
        $rows = $this->lines->groupBy('product_id')
            ->map(fn (Collection $lines) => [
                'product' => $lines->first()['product'],
                'category' => $lines->first()['category'] ?? self::NO_CATEGORY,
                'unit' => $lines->first()['unit'],
                'qty' => $lines->sum('qty'),
                'sales' => $lines->pluck('code')->unique()->count(),
                'total' => $lines->sum('total'),
                'profit' => $lines->sum('total') - $lines->sum('cost_total'),
            ])
            ->sortBy([['total', 'desc'], ['product', 'asc']])
            ->values()
            ->map(fn (array $product, int $index) => [
                $index + 1,
                $product['product'],
                $product['category'],
                $this->displayQty($product['qty'], $product['unit']),
                $product['unit'],
                $product['sales'],
                Cell::fromValue($product['total'], $this->money),
                Cell::fromValue($product['profit'], $this->money),
                Cell::fromValue($product['total'] / $revenue, $this->percent),
            ]);

        $this->table($writer, ['#', 'Produk', 'Kategori', 'Terjual', 'Satuan', 'Transaksi', 'Penjualan', 'Laba kotor', 'Porsi penjualan'], $rows, [
            'Total', '', '', '', '', '',
            $this->lines->sum('total'), $this->lines->sum('total') - $this->lines->sum('cost_total'), '',
        ], [5, 28, 18, 10, 8, 11, 16, 16, 15]);
    }

    private function categories(Writer $writer): void
    {
        $revenue = max(1, $this->lines->sum('total'));
        $rows = $this->lines->groupBy(fn (array $line) => $line['category'] ?? self::NO_CATEGORY)
            ->map(fn (Collection $lines, string $category) => [
                'category' => $category,
                'products' => $lines->pluck('product_id')->unique()->count(),
                'items' => $this->itemCount($lines),
                'total' => $lines->sum('total'),
                'profit' => $lines->sum('total') - $lines->sum('cost_total'),
            ])
            ->sortByDesc('total')
            ->values()
            ->map(fn (array $category) => [
                $category['category'],
                $category['products'],
                $category['items'],
                Cell::fromValue($category['total'], $this->money),
                Cell::fromValue($category['profit'], $this->money),
                Cell::fromValue($category['total'] / $revenue, $this->percent),
            ]);

        $this->table($writer, ['Kategori', 'Jenis produk', 'Item terjual', 'Penjualan', 'Laba kotor', 'Porsi penjualan'], $rows, [
            'Total', $this->lines->pluck('product_id')->unique()->count(), $this->itemCount($this->lines),
            $this->lines->sum('total'), $this->lines->sum('total') - $this->lines->sum('cost_total'), '',
        ], [24, 13, 13, 16, 16, 15]);
    }

    /** Stock is today's, not the report date's: there is no stock history. */
    private function lowStockSheet(Writer $writer): void
    {
        $rows = collect($this->lowStock)->map(fn (array $product) => [
            $product['name'],
            $product['category'] ?? self::NO_CATEGORY,
            $this->displayQty($product['stock'], $product['unit']),
            $product['unit'],
            $product['stock'] <= 0 ? 'Habis' : 'Menipis',
        ]);

        $this->table($writer, ['Produk', 'Kategori', 'Sisa stok', 'Satuan', 'Status'], $rows, null, [28, 18, 11, 8, 10], empty: 'Aman, tidak ada stok yang menipis.');

        $writer->addRow(new Row([]));
        $writer->addRow(new Row([Cell::fromValue(
            "Stok per {$this->generatedAt->format('d/m/Y H:i')} (saat file dibuat), batas menipis ≤ {$this->lowStockThreshold}.",
            $this->muted,
        )]));
    }

    /**
     * A header, the rows, then a totals row. The header stays in view while
     * scrolling and has a filter, which leaves the totals row out.
     *
     * @param  list<string>  $headers
     * @param  Collection<int, list<mixed>>  $rows
     * @param  list<int|string>|null  $totals  amounts become rupiah where the rows hold rupiah
     * @param  list<float>  $widths
     */
    private function table(Writer $writer, array $headers, Collection $rows, ?array $totals, array $widths, string $empty = 'Tidak ada penjualan pada tanggal ini.'): void
    {
        $sheet = $writer->getCurrentSheet();
        foreach ($widths as $index => $width) {
            $sheet->setColumnWidth($width, $index + 1);
        }
        $sheet->setSheetView(new SheetView(freezeRow: self::LETTERHEAD_ROWS + 2));

        $writer->addRow(new Row(array_map(fn ($header) => Cell::fromValue($header, $this->header), $headers)));

        if ($rows->isEmpty()) {
            $writer->addRow(new Row([Cell::fromValue($empty, $this->muted)]));

            return;
        }

        foreach ($rows as $row) {
            $writer->addRow(new Row(array_map(fn ($value) => $value instanceof Cell ? $value : Cell::fromValue($value), $row)));
        }
        $headerRow = self::LETTERHEAD_ROWS + 1;
        $sheet->setAutoFilter(new AutoFilter(0, $headerRow, count($headers) - 1, $headerRow + $rows->count()));

        if ($totals === null) {
            return;
        }

        $moneyColumns = collect($rows->first())
            ->map(fn ($value) => $value instanceof Cell && $value->style?->format === self::RUPIAH);

        $writer->addRow(new Row(array_map(
            fn ($value, $index) => Cell::fromValue(
                $value,
                is_int($value) && $moneyColumns[$index] ? $this->totalMoney : (is_int($value) ? $this->totalNumber : $this->totalLabel),
            ),
            $totals,
            array_keys($totals),
        )));
    }

    /** 1520 gram -> 1.52 (kg); pieces stay whole. */
    private function displayQty(int $qty, string $unit): int|float
    {
        $unit = Unit::from($unit);

        return $unit->isMeasured() ? $qty / $unit->scale() : $qty;
    }

    /** Same rule as Unit::itemCount(): pieces one by one, a weighed line as one. */
    private function itemCount(Collection $lines): int
    {
        return $lines->sum(fn (array $line) => Unit::from($line['unit'])->itemCount($line['qty']));
    }

    /** Sabtu, 3 Oktober 2026 */
    private function longDate(CarbonImmutable $date): string
    {
        return $date->locale('id')->translatedFormat('l, j F Y');
    }
}
