<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Application\Subscription\UseCase\ConfirmPayment;
use App\Application\Subscription\UseCase\ExpirePayment;
use App\Domain\Subscription\ValueObject\Plan;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The pretend invoice page of PAYMENT_DRIVER=fake. "Bayar" does what a Xendit
 * webhook would do, through the same use case, then returns like Xendit does.
 * Answers 404 with any other driver and in production.
 */
class PaymentSimulationController extends Controller
{
    public function show(string $payment): Response
    {
        $this->ensureEnabled();

        $found = Payment::with('store:id,name')->where('external_id', $payment)->firstOrFail();

        return Inertia::render('PaymentSimulation', [
            'payment' => [
                'externalId' => $found->external_id,
                'storeName' => $found->store->name,
                'planLabel' => Plan::from($found->plan)->label(),
                'amount' => $found->amount,
                'status' => $found->status,
                'expired' => $found->expires_at->isPast(),
            ],
        ]);
    }

    public function pay(string $payment, ConfirmPayment $confirm): RedirectResponse
    {
        $this->ensureEnabled();

        $found = Payment::where('external_id', $payment)->firstOrFail();

        if ($found->status !== 'paid' && $found->expires_at->isPast()) {
            throw ValidationException::withMessages(['payment' => 'Tagihan ini sudah kedaluwarsa. Pilih paket lagi.']);
        }

        $confirm->execute($found->external_id, $found->amount);

        return redirect()->route('subscription.finish', $found->external_id);
    }

    public function cancel(string $payment, ExpirePayment $expire): RedirectResponse
    {
        $this->ensureEnabled();

        $expire->execute($payment);

        return redirect()->route('subscription.index');
    }

    private function ensureEnabled(): void
    {
        abort_unless(config('warung.payment.driver') === 'fake' && ! app()->isProduction(), 404);
    }
}
