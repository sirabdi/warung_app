<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistence model, not an aggregate. Product business rules live in
 * App\Domain\Product\Entity\Product; this class is only a bridge to the table.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'cost_price', 'sell_price', 'stock'];

    protected $casts = [
        'cost_price' => 'integer',
        'sell_price' => 'integer',
        'stock' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
