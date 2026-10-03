<?php

namespace App\Application\Subscription\Query;

use App\Application\Shared\Query\Page;
use DateTimeImmutable;

/** Every customer store, for the app owner's dashboard. Reads across stores. */
interface StoreDirectory
{
    /** @return array{stores: int, active: int, expired: int, pending: int, expiringSoon: int, revenueThisMonth: int, revenueTotal: int} */
    public function summary(DateTimeImmutable $now, int $expiringDays): array;

    /**
     * Newest store first. $status is active|expired|pending, or null for all;
     * $search matches the store name, owner name or email.
     *
     * @return Page<array{id: int, name: string, phone: string, address: string, owner_name: ?string, owner_email: ?string, status: string, ends_at: ?string, registered_at: string, paid_total: int, paid_count: int, last_paid_at: ?string}>
     */
    public function stores(DateTimeImmutable $now, ?string $status, string $search, int $page, int $perPage = 20): Page;

    /** @return list<array{external_id: string, store_name: string, plan_label: string, amount: int, paid_at: string}> */
    public function recentPayments(int $limit = 10): array;
}
