<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Tenancy\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Persistence model without business rules; see App\Domain\Sale. */
class Transaction extends Model
{
    use BelongsToStore;

    protected $fillable = ['code', 'product_id', 'qty', 'price', 'cost_price', 'cost_total', 'total', 'sold_at', 'user_id'];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'integer',
        'cost_price' => 'integer',
        'cost_total' => 'integer',
        'total' => 'integer',
        'sold_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
