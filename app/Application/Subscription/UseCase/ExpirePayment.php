<?php

namespace App\Application\Subscription\UseCase;

use App\Domain\Subscription\Repository\PaymentRepository;

/** The gateway says an invoice ran out without being paid. Unknown ids are ignored. */
final readonly class ExpirePayment
{
    public function __construct(private PaymentRepository $payments) {}

    public function execute(string $externalId): void
    {
        $payment = $this->payments->findByExternalId($externalId);

        if ($payment === null) {
            return;
        }

        $payment->expire();
        $this->payments->save($payment);
    }
}
