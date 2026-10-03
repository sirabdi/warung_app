<?php

namespace App\Infrastructure\Subscription;

use App\Infrastructure\Notification\SubscriptionReminderNotification;
use App\Infrastructure\Persistence\Eloquent\Models\Store;

/**
 * Emails the owner 7 days and 1 day before the subscription ends (see
 * warung.subscription.reminder_days). Meant to run once a day; each reminder
 * is remembered on the store, so running it again sends nothing twice.
 */
final class SendSubscriptionReminders
{
    /** @return int emails sent */
    public function run(): int
    {
        $sent = 0;

        foreach (config('warung.subscription.reminder_days') as $days) {
            $day = today()->addDays($days);

            Store::with('owner')
                ->whereBetween('subscription_ends_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->each(function (Store $store) use ($days, &$sent) {
                    $key = $days.':'.$store->subscription_ends_at->toDateString();

                    if ($store->last_reminder === $key || $store->owner === null) {
                        return;
                    }

                    $store->owner->notify(new SubscriptionReminderNotification($store->name, $days, $store->subscription_ends_at));
                    $store->update(['last_reminder' => $key]);
                    $sent++;
                });
        }

        return $sent;
    }
}
