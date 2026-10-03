<?php

namespace App\Presentation\Http\Controllers\Admin;

use App\Application\Shared\Clock;
use App\Application\Subscription\Query\StoreDirectory;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The app owner's view: who registered, who pays, and what came in. */
class DashboardController extends Controller
{
    /** "Segera habis" on the dashboard, the same week the reminder emails cover. */
    private const EXPIRING_DAYS = 7;

    public function __invoke(Request $request, StoreDirectory $directory, Clock $clock): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $status = $filters['status'] ?? null;
        $search = trim($filters['search'] ?? '');
        $now = $clock->now();

        return Inertia::render('Admin/Dashboard', [
            'summary' => fn () => $directory->summary($now, self::EXPIRING_DAYS),
            'stores' => fn () => $directory->stores($now, $status, $search, (int) ($filters['page'] ?? 1))->toArray(),
            'recentPayments' => fn () => $directory->recentPayments(),
            'filters' => ['status' => $status, 'search' => $search],
            'expiringDays' => self::EXPIRING_DAYS,
        ]);
    }
}
