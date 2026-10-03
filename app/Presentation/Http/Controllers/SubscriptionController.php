<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Shared\Clock;
use App\Application\Subscription\DTO\Payer;
use App\Application\Subscription\Query\PaymentHistory;
use App\Application\Subscription\UseCase\StartCheckout;
use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Subscription\Repository\PaymentRepository;
use App\Domain\Subscription\Repository\SubscriptionRepository;
use App\Domain\Subscription\ValueObject\Plan;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Choosing a plan, paying it, and the "Langganan Habis" page. Open to every
 * logged-in store, whatever its subscription status.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
        private readonly Clock $clock,
    ) {}

    public function index(Request $request, PaymentHistory $history): Response
    {
        $subscription = $this->subscription($request);
        $basePrice = Plan::OneMonth->price()->amount;

        return Inertia::render('Subscription', [
            'status' => $subscription->status($this->clock->now())->value,
            'endsAt' => $subscription->endsAt()?->format(DATE_ATOM),
            'plans' => array_map(fn (Plan $plan) => [
                'value' => $plan->value,
                'label' => $plan->label(),
                'price' => $plan->price()->amount,
                'perMonth' => intdiv($plan->price()->amount, $plan->months()),
                // Compared with paying month by month.
                'saving' => $basePrice * $plan->months() - $plan->price()->amount,
            ], Plan::cases()),
            'payments' => $history->latest($request->user()->store_id),
            'testMode' => config('warung.payment.driver') === 'fake',
        ]);
    }

    public function checkout(Request $request, StartCheckout $checkout): HttpResponse
    {
        $plan = Plan::from((int) $request->validate(
            ['plan' => ['required', Rule::enum(Plan::class)]],
            ['plan.required' => 'Pilih paket dulu.', 'plan.enum' => 'Paket tidak dikenal.'],
        )['plan']);

        $user = $request->user();
        $payment = $checkout->execute($user->store_id, $plan, new Payer($user->name, $user->email, $user->store->name));

        // The gateway's page lives on another site: a full browser redirect.
        return Inertia::location($payment->checkoutUrl());
    }

    /** Where the gateway sends the customer back. The webhook may still be on its way. */
    public function finish(Request $request, string $payment, PaymentRepository $payments): Response|RedirectResponse
    {
        $found = $payments->findByExternalId($payment);
        abort_if($found === null || $found->storeId() !== $request->user()->store_id, 404);

        if ($found->isPaid()) {
            return redirect()->route('cashier')->with(
                'success',
                'Pembayaran berhasil. Langganan aktif sampai '.$found->periodEndsAt()->format('d/m/Y').'.',
            );
        }

        return Inertia::render('PaymentFinish', [
            'payment' => [
                'externalId' => $found->externalId(),
                'status' => $found->status()->value,
                'planLabel' => $found->plan()->label(),
                'amount' => $found->amount()->amount,
                'checkoutUrl' => $found->isPayable($this->clock->now()) ? $found->checkoutUrl() : null,
            ],
        ]);
    }

    public function expired(Request $request): Response|RedirectResponse
    {
        $subscription = $this->subscription($request);

        return match ($subscription->status($this->clock->now())) {
            SubscriptionStatus::Active => redirect()->route('cashier'),
            SubscriptionStatus::Pending => redirect()->route('subscription.index'),
            SubscriptionStatus::Expired => Inertia::render('SubscriptionExpired', [
                'storeName' => $request->user()->store->name,
                'endsAt' => $subscription->endsAt()->format(DATE_ATOM),
            ]),
        };
    }

    private function subscription(Request $request): Subscription
    {
        return $this->subscriptions->find($request->user()->store_id)
            ?? Subscription::reconstitute($request->user()->store_id, null);
    }
}
