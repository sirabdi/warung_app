<?php

namespace App\Application\Subscription\Query;

interface PaymentHistory
{
    /**
     * Newest first.
     *
     * @return list<array{external_id: string, plan: int, plan_label: string, amount: int, status: string, created_at: string, paid_at: ?string, period_ends_at: ?string, checkout_url: ?string, payable: bool}>
     */
    public function latest(int $storeId, int $limit = 10): array;
}
