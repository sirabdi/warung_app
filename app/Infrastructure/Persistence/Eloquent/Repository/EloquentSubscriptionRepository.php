<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Subscription\Repository\SubscriptionRepository;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use RuntimeException;

/** A subscription is the subscription_ends_at column of its store. */
final class EloquentSubscriptionRepository implements SubscriptionRepository
{
    public function find(int $storeId): ?Subscription
    {
        $store = Store::find($storeId, ['id', 'subscription_ends_at']);

        return $store ? $this->toDomain($store) : null;
    }

    public function lock(int $storeId): Subscription
    {
        $store = Store::whereKey($storeId)->lockForUpdate()->first(['id', 'subscription_ends_at'])
            ?? throw new RuntimeException("Toko #{$storeId} tidak ditemukan.");

        return $this->toDomain($store);
    }

    public function save(Subscription $subscription): void
    {
        Store::whereKey($subscription->storeId())->update([
            'subscription_ends_at' => $subscription->endsAt(),
        ]);
    }

    private function toDomain(Store $store): Subscription
    {
        return Subscription::reconstitute($store->id, $store->subscription_ends_at?->toDateTimeImmutable());
    }
}
