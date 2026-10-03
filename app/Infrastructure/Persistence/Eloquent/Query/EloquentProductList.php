<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Product\Query\ProductList;
use App\Application\Shared\Query\Page;
use App\Domain\Product\Entity\Product as ProductEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final class EloquentProductList implements ProductList
{
    use PaginatesQueries;

    private const COLUMNS = [
        'products.id', 'products.name', 'products.category_id', 'categories.name as category_name', 'products.unit',
        'products.cost_price', 'products.sell_price', 'products.stock',
    ];

    public function paginate(string $search, int $page, int $perPage, ?int $categoryId = null): Page
    {
        $query = $this->query()
            ->when($search !== '', fn ($query) => $this->whereContains($query, 'products.name', $search))
            ->when($categoryId, fn ($query) => $query->where('products.category_id', $categoryId))
            ->orderBy('products.name')
            ->orderBy('products.id');

        return $this->page($query, $page, $perPage, self::COLUMNS, fn (Product $product) => $this->row($product));
    }

    public function similar(string $name, ?int $exceptId = null, int $limit = 5): array
    {
        $key = ProductEntity::nameKey($name);
        if (mb_strlen($key) < 2) {
            return [];
        }

        // "Aqua 600 ml" finds "Aqua 600ml": every word is looked up on its own.
        // One-letter words would match nearly everything.
        $words = array_values(array_unique(array_filter(explode(' ', $key), fn ($word) => mb_strlen($word) >= 2))) ?: [$key];
        $score = implode(' + ', array_fill(0, count($words), "case when lower(products.name) like ? escape '!' then 1 else 0 end"));
        $patterns = array_map(fn ($word) => '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%', $words);

        return $this->query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereRaw("({$score}) >= ?", [...$patterns, (int) ceil(count($words) / 2)])
            ->orderByRaw('case when lower(products.name) = ? then 0 else 1 end', [$key])
            ->orderByRaw("({$score}) desc", $patterns)
            ->orderBy('products.name')
            ->limit($limit)
            ->get(self::COLUMNS)
            ->map(fn (Product $product) => [
                ...$this->row($product),
                'exact' => ProductEntity::nameKey($product->name) === $key,
            ])
            ->all();
    }

    public function find(int $id): ?array
    {
        $product = $this->query()->where('products.id', $id)->first(self::COLUMNS);

        return $product ? $this->row($product) : null;
    }

    private function query(): Builder
    {
        return Product::query()->leftJoin('categories', 'categories.id', '=', 'products.category_id');
    }

    private function row(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category_name,
            'unit' => $product->unit,
            'cost_price' => $product->cost_price,
            'sell_price' => $product->sell_price,
            'stock' => $product->stock,
        ];
    }
}
