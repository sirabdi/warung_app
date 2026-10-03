<?php

namespace App\Application\Subscription\UseCase;

use App\Application\Shared\Clock;
use App\Application\Subscription\DTO\Payer;
use App\Application\Subscription\Port\PaymentGateway;
use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Repository\PaymentRepository;
use App\Domain\Subscription\ValueObject\Plan;

/**
 * Creates (or reuses) an invoice for a plan and returns where to pay it.
 *
 * Clicking "Bayar" twice opens the same invoice instead of a second one, so a
 * customer can never pay the same package twice by accident.
 */
final readonly class StartCheckout
{
    /** How long an invoice can be paid. */
    public const INVOICE_HOURS = 24;

    public function __construct(
        private PaymentRepository $payments,
        private PaymentGateway $gateway,
        private Clock $clock,
    ) {}

    public function execute(int $storeId, Plan $plan, Payer $payer): Payment
    {
        $now = $this->clock->now();

        $pending = $this->payments->latestPending($storeId, $plan);
        if ($pending?->isPayable($now)) {
            return $pending;
        }

        $payment = $this->payments->save(Payment::start(
            $storeId,
            $plan,
            self::newExternalId(),
            $now->modify('+'.self::INVOICE_HOURS.' hours'),
        ));

        // Outside a database transaction on purpose: it is an HTTP call. If it
        // fails the payment stays pending without a link and simply expires.
        $invoice = $this->gateway->createInvoice($payment, $payer);
        $payment->attachInvoice($invoice->id, $invoice->checkoutUrl);

        return $this->payments->save($payment);
    }

    /** WRG-20261003-8F3A1C2B: readable in the gateway dashboard, impossible to guess. */
    private static function newExternalId(): string
    {
        return 'WRG-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
    }
}
