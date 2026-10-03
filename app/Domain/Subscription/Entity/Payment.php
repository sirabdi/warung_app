<?php

namespace App\Domain\Subscription\Entity;

use App\Domain\Shared\ValueObject\Money;
use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Domain\Subscription\ValueObject\Plan;
use DateTimeImmutable;

/**
 * One checkout of one plan. The amount is fixed when the checkout starts, so a
 * later price change never alters an invoice the customer already holds.
 *
 * The external id is ours and is sent to the payment gateway; the gateway
 * reports back with it.
 */
final class Payment
{
    private function __construct(
        private ?int $id,
        private readonly int $storeId,
        private readonly Plan $plan,
        private readonly Money $amount,
        private readonly string $externalId,
        private PaymentStatus $status,
        private readonly DateTimeImmutable $expiresAt,
        private ?string $invoiceId = null,
        private ?string $checkoutUrl = null,
        private ?DateTimeImmutable $paidAt = null,
        private ?DateTimeImmutable $periodEndsAt = null,
    ) {}

    public static function start(int $storeId, Plan $plan, string $externalId, DateTimeImmutable $expiresAt): self
    {
        return new self(null, $storeId, $plan, $plan->price(), $externalId, PaymentStatus::Pending, $expiresAt);
    }

    /** Used by repositories to rebuild a payment from the database. */
    public static function reconstitute(
        int $id,
        int $storeId,
        Plan $plan,
        Money $amount,
        string $externalId,
        PaymentStatus $status,
        DateTimeImmutable $expiresAt,
        ?string $invoiceId,
        ?string $checkoutUrl,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $periodEndsAt,
    ): self {
        return new self($id, $storeId, $plan, $amount, $externalId, $status, $expiresAt, $invoiceId, $checkoutUrl, $paidAt, $periodEndsAt);
    }

    public function attachInvoice(string $invoiceId, string $checkoutUrl): void
    {
        $this->invoiceId = $invoiceId;
        $this->checkoutUrl = $checkoutUrl;
    }

    /**
     * Also accepted after the invoice was marked expired on our side: when the
     * gateway says the money arrived, the customer gets what they paid for.
     */
    public function markPaid(DateTimeImmutable $paidAt, DateTimeImmutable $periodEndsAt): void
    {
        $this->status = PaymentStatus::Paid;
        $this->paidAt = $paidAt;
        $this->periodEndsAt = $periodEndsAt;
    }

    public function expire(): void
    {
        if ($this->status === PaymentStatus::Pending) {
            $this->status = PaymentStatus::Expired;
        }
    }

    /** A pending invoice that can still be paid, so a new checkout may reuse it. */
    public function isPayable(DateTimeImmutable $now): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->checkoutUrl !== null
            && $this->expiresAt > $now;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function assignId(int $id): void
    {
        $this->id ??= $id;
    }

    public function storeId(): int
    {
        return $this->storeId;
    }

    public function plan(): Plan
    {
        return $this->plan;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function invoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function checkoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function periodEndsAt(): ?DateTimeImmutable
    {
        return $this->periodEndsAt;
    }
}
