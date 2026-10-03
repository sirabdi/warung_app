<?php

namespace App\Application\Subscription\Port;

use App\Application\Subscription\DTO\Invoice;
use App\Application\Subscription\DTO\Payer;
use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Exception\CheckoutFailed;

/**
 * Where the customer actually pays (Xendit, or a local simulation).
 *
 * Creating an invoice is all the application asks for. The gateway reports
 * the result later through a webhook, which ends up in ConfirmPayment.
 */
interface PaymentGateway
{
    /** @throws CheckoutFailed when the gateway cannot be reached */
    public function createInvoice(Payment $payment, Payer $payer): Invoice;
}
