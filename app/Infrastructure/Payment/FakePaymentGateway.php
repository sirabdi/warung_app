<?php

namespace App\Infrastructure\Payment;

use App\Application\Subscription\DTO\Invoice;
use App\Application\Subscription\DTO\Payer;
use App\Application\Subscription\Port\PaymentGateway;
use App\Domain\Subscription\Entity\Payment;

/**
 * Stand-in for Xendit while developing: the "invoice" is a page in this app
 * with a pay button (PAYMENT_DRIVER=fake). Paying there goes through the same
 * ConfirmPayment use case as a real webhook. Never available in production.
 */
final class FakePaymentGateway implements PaymentGateway
{
    public function createInvoice(Payment $payment, Payer $payer): Invoice
    {
        return new Invoice(
            'fake-'.$payment->externalId(),
            route('payments.simulate', $payment->externalId()),
        );
    }
}
