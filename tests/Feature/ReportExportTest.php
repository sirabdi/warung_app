<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Bu Sri']);
        $this->user->store->update(['name' => 'Warung Bu Sri', 'address' => 'Jl. Melati 5, Bogor']);
        $this->actingAs($this->user);
    }

    /** @var array<string, list<mixed>> sheet name => its store name, address and subtitle */
    private array $letterheads = [];

    /**
     * Below the letterhead, so row 0 is a table's header.
     *
     * @return array<string, list<list<mixed>>> sheet name => rows of cell values
     */
    private function sheets(TestResponse $response): array
    {
        $reader = new Reader;
        $reader->open($response->baseResponse->getFile()->getPathname());

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
            }
        }
        $reader->close();

        // The reader skips the blank row under the letterhead.
        foreach ($sheets as $name => $rows) {
            $this->letterheads[$name] = array_map(fn ($row) => $row[0], array_slice($rows, 0, 3));
            $sheets[$name] = array_slice($rows, 3);
        }

        return $sheets;
    }

    public function test_the_day_is_exported_as_a_workbook(): void
    {
        $drinks = Category::factory()->create(['name' => 'Minuman']);
        $rice = Product::factory()->create([
            'name' => 'Beras', 'category_id' => null, 'unit' => 'kg', 'stock' => 25000, 'sell_price' => 14000, 'cost_price' => 12000,
        ]);
        $coffee = Product::factory()->create([
            'name' => 'Kopi', 'category_id' => $drinks->id, 'stock' => 50, 'sell_price' => 2000, 'cost_price' => 1200,
        ]);
        Product::factory()->create(['name' => 'Gula', 'stock' => 0]);

        // 1,52 kg rice = Rp 21.300 plus 3 coffees = Rp 6.000, then one more coffee.
        $this->postJson('/api/sales', ['items' => [
            ['product_id' => $rice->id, 'qty' => 1520],
            ['product_id' => $coffee->id, 'qty' => 3],
        ]])->assertCreated();
        $this->postJson('/api/sales', ['items' => [['product_id' => $coffee->id, 'qty' => 1]]])->assertCreated();

        // Another day stays out of the file.
        Transaction::create([
            'code' => 'YESTERDAY', 'product_id' => $coffee->id, 'qty' => 9, 'price' => 2000,
            'cost_price' => 1200, 'cost_total' => 10800, 'total' => 18000, 'sold_at' => now()->subDay(),
        ]);

        $response = $this->get('/report/export?date='.today()->toDateString());

        $response->assertOk()->assertDownload('Laporan-warung-bu-sri-'.today()->toDateString().'.xlsx');
        $sheets = $this->sheets($response);

        $this->assertSame(['Ringkasan', 'Transaksi', 'Rincian item', 'Per produk', 'Per kategori', 'Stok menipis'], array_keys($sheets));

        // Every sheet says which store and day it is about.
        foreach ($this->letterheads as $name => $letterhead) {
            $this->assertSame(['Warung Bu Sri', 'Jl. Melati 5, Bogor'], array_slice($letterhead, 0, 2));
            $this->assertStringContainsString($name, $letterhead[2]);
        }

        $summary = collect($sheets['Ringkasan'])->filter(fn ($row) => count($row) === 2)->mapWithKeys(fn ($row) => [$row[0] => $row[1]]);
        $this->assertEquals(29300, $summary['Penjualan']);
        $this->assertEquals((21300 - 18240) + (8000 - 4800), $summary['Laba kotor']);
        $this->assertEquals(2, $summary['Jumlah transaksi']);
        $this->assertEquals(5, $summary['Item terjual']); // 1 weighed line + 4 coffees

        $sales = $sheets['Transaksi'];
        $this->assertSame(['Kode', 'Jam', 'Kasir', 'Jenis produk', 'Item', 'Total', 'Laba kotor'], $sales[0]);
        $this->assertCount(4, $sales); // header, two receipts, totals
        $this->assertSame('Bu Sri', $sales[1][2]);
        $this->assertEquals(27300, $sales[1][5]);
        $this->assertSame('Total', $sales[3][0]);
        $this->assertEquals(29300, $sales[3][5]);

        // Weighed goods in kg, not grams; no category reads as such.
        $rows = collect($sheets['Rincian item'])->slice(1, 3)->keyBy(2);
        $this->assertEquals(1.52, $rows['Beras'][4]);
        $this->assertSame('kg', $rows['Beras'][5]);
        $this->assertSame('Tanpa kategori', $rows['Beras'][3]);

        // Every product sold, by revenue.
        $products = $sheets['Per produk'];
        $this->assertSame('Beras', $products[1][1]);
        $this->assertSame('Kopi', $products[2][1]);
        $this->assertEquals(4, $products[2][3]);
        $this->assertEquals(2, $products[2][5]);

        $categories = collect($sheets['Per kategori'])->slice(1)->keyBy(0);
        $this->assertEquals(8000, $categories['Minuman'][3]);
        $this->assertEquals(21300, $categories['Tanpa kategori'][3]);

        $this->assertSame(['Gula', 'Habis'], [$sheets['Stok menipis'][1][0], $sheets['Stok menipis'][1][4]]);
    }

    public function test_a_day_without_sales_still_exports(): void
    {
        $sheets = $this->sheets($this->get('/report/export?date=2020-01-01')->assertOk());

        $this->assertSame('Tidak ada penjualan pada tanggal ini.', $sheets['Transaksi'][1][0]);
        $this->assertSame('Rabu, 1 Januari 2020', collect($sheets['Ringkasan'])->firstWhere(0, 'Tanggal laporan')[1]);
    }

    public function test_a_missing_address_shows_a_dash(): void
    {
        $this->user->store->update(['address' => '']);

        $this->sheets($this->get('/report/export')->assertOk());

        $this->assertSame('-', $this->letterheads['Transaksi'][1]);
    }

    public function test_another_stores_sales_stay_out(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        $product = Product::factory()->create(['name' => 'Rahasia', 'stock' => 10]);
        $this->postJson('/api/sales', ['items' => [['product_id' => $product->id, 'qty' => 1]]])->assertCreated();

        $this->actingAs($this->user);
        $sheets = $this->sheets($this->get('/report/export')->assertOk());

        $this->assertSame('Tidak ada penjualan pada tanggal ini.', $sheets['Rincian item'][1][0]);
        $this->assertNull(collect($sheets['Stok menipis'])->firstWhere(0, 'Rahasia'));
    }

    public function test_guests_cannot_export(): void
    {
        auth()->logout();

        $this->get('/report/export')->assertRedirect('/login');
    }
}
