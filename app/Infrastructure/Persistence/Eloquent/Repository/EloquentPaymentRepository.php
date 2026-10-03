<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Shared\ValueObject\Money;
use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Repository\PaymentRepository;
use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Domain\Subscription\ValueObject\Plan;
use App\Infrastructure\Persistence\Eloquent\Models\Payment as PaymentModel;

final class EloquentPaymentRepository implements PaymentRepository
{
    public function findByExternalId(string $externalId): ?Payment
    {
        $model = PaymentModel::firstWhere('external_id', $externalId);

        return $model ? $this->toDomain($model) : null;
    }

    public function lockByExternalId(string $externalId): ?Payment
    {
        $model = PaymentModel::where('external_id', $externalId)->lockForUpdate()->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function latestPending(int $storeId, Plan $plan): ?Payment
    {
        $model = PaymentModel::where('store_id', $storeId)
            ->where('plan', $plan->value)
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Payment $payment): Payment
    {
        $model = $payment->id() !== null
            ? PaymentModel::findOrFail($payment->id())
            : new PaymentModel(['gateway' => config('warung.payment.driver')]);

        $model->fill([
            'store_id' => $payment->storeId(),
            'plan' => $payment->plan()->value,
            'amount' => $payment->amount()->amount,
            'status' => $payment->status()->value,
            'external_id' => $payment->externalId(),
            'invoice_id' => $payment->invoiceId(),
            'checkout_url' => $payment->checkoutUrl(),
            'expires_at' => $payment->expiresAt(),
            'paid_at' => $payment->paidAt(),
            'period_ends_at' => $payment->periodEndsAt(),
        ])->save();

        $payment->assignId($model->id);

        return $payment;
    }

    private function toDomain(PaymentModel $model): Payment
    {
        return Payment::reconstitute(
            $model->id,
            $model->store_id,
            Plan::from($model->plan),
            Money::of($model->amount),
            $model->external_id,
            PaymentStatus::from($model->status),
            $model->expires_at->toDateTimeImmutable(),
            $model->invoice_id,
            $model->checkout_url,
            $model->paid_at?->toDateTimeImmutable(),
            $model->period_ends_at?->toDateTimeImmutable(),
        );
    }
}
