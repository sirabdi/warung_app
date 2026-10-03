<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A customer's store. Persistence model only: the subscription rules live in
 * App\Domain\Subscription\Entity\Subscription.
 */
class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address', 'subscription_ends_at', 'last_reminder'];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    /** One store, one login. */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }
}
