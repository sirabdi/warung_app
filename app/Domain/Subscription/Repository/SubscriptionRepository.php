<?php

namespace App\Domain\Subscription\Repository;

use App\Domain\Subscription\Entity\Subscription;

interface SubscriptionRepository
{
    public function find(int $storeId): ?Subscription;

    /** Locked until the surrounding transaction ends, so two payments cannot both extend from the same end date. */
    public function lock(int $storeId): Subscription;

    public function save(Subscription $subscription): void;
}
