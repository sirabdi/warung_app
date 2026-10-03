<?php

namespace App\Presentation\Http\Middleware;

use App\Application\Shared\Clock;
use App\Domain\Subscription\Repository\SubscriptionRepository;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The till, products and reports are for paying stores only.
 *
 * Never paid → choose a plan. Paid before but over → "Langganan Habis". The
 * JSON API answers 402 so an open page can send the user there too.
 */
class EnsureSubscriptionActive
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions,
        private readonly Clock $clock,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->subscriptions->find($request->user()->store_id)?->status($this->clock->now())
            ?? SubscriptionStatus::Pending;

        if ($status === SubscriptionStatus::Active) {
            return $next($request);
        }

        $route = $status === SubscriptionStatus::Expired ? 'subscription.expired' : 'subscription.index';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $status === SubscriptionStatus::Expired
                    ? 'Langganan habis. Perpanjang dulu untuk lanjut berjualan.'
                    : 'Pilih paket dan selesaikan pembayaran dulu.',
                'redirect' => route($route),
            ], 402);
        }

        return redirect()->route($route);
    }
}
