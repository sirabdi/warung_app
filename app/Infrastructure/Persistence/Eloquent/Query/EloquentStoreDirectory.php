<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Shared\Query\Page;
use App\Application\Subscription\Query\StoreDirectory;
use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Domain\Subscription\ValueObject\Plan;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

/** Store and Payment carry no store scope, so these queries see every store. */
final class EloquentStoreDirectory implements StoreDirectory
{
    use PaginatesQueries;

    public function summary(DateTimeImmutable $now, int $expiringDays): array
    {
        $count = fn (?string $status) => $this->withStatus(Store::query(), $status, $now)->count();
        $paid = fn () => Payment::where('status', PaymentStatus::Paid->value);
        $monthStart = $now->modify('first day of this month')->setTime(0, 0);

        return [
            'stores' => $count(null),
            'active' => $count(SubscriptionStatus::Active->value),
            'expired' => $count(SubscriptionStatus::Expired->value),
            'pending' => $count(SubscriptionStatus::Pending->value),
            'expiringSoon' => Store::where('subscription_ends_at', '>', $now)
                ->where('subscription_ends_at', '<=', $now->modify("+{$expiringDays} days"))
                ->count(),
            'revenueThisMonth' => (int) $paid()->where('paid_at', '>=', $monthStart)->sum('amount'),
            'revenueTotal' => (int) $paid()->sum('amount'),
        ];
    }

    public function stores(DateTimeImmutable $now, ?string $status, string $search, int $page, int $perPage = 20): Page
    {
        $paid = fn ($query) => $query->where('status', PaymentStatus::Paid->value);

        $query = $this->withStatus(Store::query(), $status, $now)
            ->with('owner:id,store_id,name,email')
            ->withSum(['payments as paid_total' => $paid], 'amount')
            ->withCount(['payments as paid_count' => $paid])
            ->withMax(['payments as last_paid_at' => $paid], 'paid_at')
            ->latest('id');

        if ($search !== '') {
            $query->where(fn (Builder $q) => $this->whereContains($q, 'name', $search)
                ->orWhereHas('owner', fn (Builder $owner) => $this->whereContains($owner, 'name', $search)
                    ->orWhere(fn (Builder $email) => $this->whereContains($email, 'email', $search))));
        }

        return $this->page($query, $page, $perPage, ['*'], fn (Store $store) => [
            'id' => $store->id,
            'name' => $store->name,
            'phone' => $store->phone,
            'address' => $store->address,
            'owner_name' => $store->owner?->name,
            'owner_email' => $store->owner?->email,
            'status' => match (true) {
                $store->subscription_ends_at === null => SubscriptionStatus::Pending->value,
                $store->subscription_ends_at > $now => SubscriptionStatus::Active->value,
                default => SubscriptionStatus::Expired->value,
            },
            'ends_at' => $store->subscription_ends_at?->toIso8601String(),
            'registered_at' => $store->created_at->toIso8601String(),
            'paid_total' => (int) $store->paid_total,
            'paid_count' => (int) $store->paid_count,
            'last_paid_at' => $store->last_paid_at ? now()->parse($store->last_paid_at)->toIso8601String() : null,
        ]);
    }

    public function recentPayments(int $limit = 10): array
    {
        return Payment::where('status', PaymentStatus::Paid->value)
            ->with('store:id,name')
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment) => [
                'external_id' => $payment->external_id,
                'store_name' => $payment->store?->name ?? '—',
                'plan_label' => Plan::from($payment->plan)->label(),
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at->toIso8601String(),
            ])
            ->all();
    }

    /** The same rule as the Subscription entity, written as SQL. */
    private function withStatus(Builder $query, ?string $status, DateTimeImmutable $now): Builder
    {
        return match ($status) {
            SubscriptionStatus::Pending->value => $query->whereNull('subscription_ends_at'),
            SubscriptionStatus::Active->value => $query->where('subscription_ends_at', '>', $now),
            SubscriptionStatus::Expired->value => $query->where('subscription_ends_at', '<=', $now),
            default => $query,
        };
    }
}
