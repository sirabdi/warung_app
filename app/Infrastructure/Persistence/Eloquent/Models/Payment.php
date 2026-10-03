<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistence model; see App\Domain\Subscription\Entity\Payment. Not scoped to
 * the current store: webhooks look payments up without anyone logged in.
 */
class Payment extends Model
{
    protected $fillable = [
        'store_id', 'plan', 'amount', 'status', 'external_id', 'gateway', 'invoice_id',
        'checkout_url', 'expires_at', 'paid_at', 'period_ends_at',
    ];

    protected $casts = [
        'plan' => 'integer',
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'period_ends_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
