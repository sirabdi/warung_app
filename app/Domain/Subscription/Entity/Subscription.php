<?php

namespace App\Domain\Subscription\Entity;

use App\Domain\Subscription\ValueObject\Plan;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use DateTimeImmutable;

/**
 * The paid period of one store. A store without an end date has never paid.
 *
 * Paying extends the period from its end when it is still running, so
 * renewing early never loses days; after it has ended it starts again from
 * the moment of payment.
 */
final class Subscription
{
    private function __construct(
        private readonly int $storeId,
        private ?DateTimeImmutable $endsAt,
    ) {}

    public static function reconstitute(int $storeId, ?DateTimeImmutable $endsAt): self
    {
        return new self($storeId, $endsAt);
    }

    public function extend(Plan $plan, DateTimeImmutable $now): DateTimeImmutable
    {
        $from = $this->isActive($now) ? $this->endsAt : $now;

        return $this->endsAt = $plan->addTo($from);
    }

    public function status(DateTimeImmutable $now): SubscriptionStatus
    {
        return match (true) {
            $this->endsAt === null => SubscriptionStatus::Pending,
            $this->isActive($now) => SubscriptionStatus::Active,
            default => SubscriptionStatus::Expired,
        };
    }

    public function isActive(DateTimeImmutable $now): bool
    {
        return $this->endsAt !== null && $this->endsAt > $now;
    }

    public function storeId(): int
    {
        return $this->storeId;
    }

    public function endsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }
}
