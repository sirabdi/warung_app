<?php

namespace App\Domain\Subscription\Repository;

use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\ValueObject\Plan;

interface PaymentRepository
{
    public function findByExternalId(string $externalId): ?Payment;

    /** Locked until the surrounding transaction ends: a webhook may arrive twice at once. */
    public function lockByExternalId(string $externalId): ?Payment;

    /** The newest pending payment of this store for this plan, if any. */
    public function latestPending(int $storeId, Plan $plan): ?Payment;

    public function save(Payment $payment): Payment;
}
