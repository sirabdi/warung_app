<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Subscription\Query\PaymentHistory;
use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Domain\Subscription\ValueObject\Plan;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;

final class EloquentPaymentHistory implements PaymentHistory
{
    public function latest(int $storeId, int $limit = 10): array
    {
        return Payment::where('store_id', $storeId)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment) => [
                'external_id' => $payment->external_id,
                'plan' => $payment->plan,
                'plan_label' => Plan::from($payment->plan)->label(),
                'amount' => $payment->amount,
                'status' => $payment->status,
                'created_at' => $payment->created_at->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'period_ends_at' => $payment->period_ends_at?->toIso8601String(),
                'checkout_url' => $payment->checkout_url,
                'payable' => $payment->status === PaymentStatus::Pending->value
                    && $payment->checkout_url !== null
                    && $payment->expires_at->isFuture(),
            ])
            ->all();
    }
}
