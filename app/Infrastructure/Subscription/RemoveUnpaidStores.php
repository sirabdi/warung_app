<?php

namespace App\Infrastructure\Subscription;

use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\Store;

/**
 * Housekeeping, meant to run every hour:
 *  - invoices past their time become expired (the fake gateway never says so),
 *  - stores that registered more than 24 hours ago and never paid are deleted
 *    with their login and categories.
 *
 * A store with an invoice that can still be paid is kept until that invoice
 * runs out: deleting it could take money for an account that no longer exists.
 */
final class RemoveUnpaidStores
{
    /** @return array{expired: int, removed: int} */
    public function run(): array
    {
        $expired = Payment::where('status', PaymentStatus::Pending->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => PaymentStatus::Expired->value]);

        $stores = Store::whereNull('subscription_ends_at')
            ->where('created_at', '<=', now()->subHours((int) config('warung.subscription.unpaid_store_hours')))
            ->whereDoesntHave('payments', fn ($query) => $query->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Paid->value]))
            ->pluck('id');

        // Users, categories and payments go with it (foreign keys cascade).
        // An unpaid store never reached the till, so it has no products or sales.
        Store::whereKey($stores)->delete();

        return ['expired' => $expired, 'removed' => $stores->count()];
    }
}
