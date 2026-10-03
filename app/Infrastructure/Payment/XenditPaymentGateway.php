<?php

namespace App\Infrastructure\Payment;

use App\Application\Subscription\DTO\Invoice;
use App\Application\Subscription\DTO\Payer;
use App\Application\Subscription\Port\PaymentGateway;
use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Exception\CheckoutFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Xendit Invoice API (PAYMENT_DRIVER=xendit). The customer pays on Xendit's own
 * page, by QRIS, virtual account, e-wallet or card, so payment details never
 * pass through this app. A test-mode key (xnd_development_…) uses fake money.
 *
 * The result arrives at XenditWebhookController.
 *
 * @see https://developers.xendit.co/api-reference/#create-invoice
 */
final class XenditPaymentGateway implements PaymentGateway
{
    public function createInvoice(Payment $payment, Payer $payer): Invoice
    {
        $seconds = max(60, $payment->expiresAt()->getTimestamp() - time());

        try {
            $response = Http::baseUrl(config('services.xendit.base_url'))
                ->withBasicAuth((string) config('services.xendit.secret_key'), '')
                ->acceptJson()
                ->timeout(15)
                ->post('/v2/invoices', [
                    'external_id' => $payment->externalId(),
                    'amount' => $payment->amount()->amount,
                    'currency' => 'IDR',
                    'description' => 'Langganan '.config('app.name').' '.$payment->plan()->label().' — '.$payer->storeName,
                    'invoice_duration' => $seconds,
                    'payer_email' => $payer->email,
                    'customer' => ['given_names' => $payer->name, 'email' => $payer->email],
                    'success_redirect_url' => route('subscription.finish', $payment->externalId()),
                    'failure_redirect_url' => route('subscription.index'),
                    'locale' => 'id',
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            report($e);

            throw CheckoutFailed::gatewayUnavailable();
        }

        return new Invoice($response->json('id'), $response->json('invoice_url'));
    }
}
