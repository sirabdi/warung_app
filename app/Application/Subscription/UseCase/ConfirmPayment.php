<?php

namespace App\Application\Subscription\UseCase;

use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Exception\PaymentAmountMismatch;
use App\Domain\Subscription\Exception\PaymentNotFound;
use App\Domain\Subscription\Repository\PaymentRepository;
use App\Domain\Subscription\Repository\SubscriptionRepository;

/**
 * The gateway says an invoice was paid: extend the store's subscription.
 *
 * Gateways retry webhooks, sometimes in parallel. The payment row is locked
 * and a payment that is already paid is returned untouched, so the period is
 * extended exactly once.
 */
final readonly class ConfirmPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {}

    public function execute(string $externalId, int $paidAmount): Payment
    {
        return $this->transactions->run(function () use ($externalId, $paidAmount) {
            $payment = $this->payments->lockByExternalId($externalId)
                ?? throw PaymentNotFound::withExternalId($externalId);

            if ($payment->isPaid()) {
                return $payment;
            }

            if ($paidAmount !== $payment->amount()->amount) {
                throw PaymentAmountMismatch::of($externalId, $payment->amount(), $paidAmount);
            }

            $now = $this->clock->now();
            $subscription = $this->subscriptions->lock($payment->storeId());
            $endsAt = $subscription->extend($payment->plan(), $now);
            $payment->markPaid($now, $endsAt);

            $this->subscriptions->save($subscription);

            return $this->payments->save($payment);
        });
    }
}
