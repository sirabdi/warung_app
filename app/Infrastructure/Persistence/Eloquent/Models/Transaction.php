<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Persistence model without business rules; see App\Domain\Sale. */
class Transaction extends Model
{
    protected $fillable = ['code', 'product_id', 'qty', 'price', 'cost_price', 'total', 'sold_at', 'user_id'];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'integer',
        'cost_price' => 'integer',
        'total' => 'integer',
        'sold_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
