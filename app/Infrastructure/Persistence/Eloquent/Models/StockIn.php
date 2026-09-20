<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Persistence model without business rules; see App\Domain\Inventory. */
class StockIn extends Model
{
    protected $fillable = ['product_id', 'qty', 'date', 'user_id'];

    protected $casts = [
        'qty' => 'integer',
        'date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
