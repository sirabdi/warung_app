<?php

namespace Tests\Unit\Domain;

use App\Domain\Subscription\Entity\Payment;
use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Subscription\ValueObject\PaymentStatus;
use App\Domain\Subscription\ValueObject\Plan;
use App\Domain\Subscription\ValueObject\SubscriptionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** Pure domain test: no Laravel, no database. */
class SubscriptionTest extends TestCase
{
    public function test_plan_prices(): void
    {
        $prices = array_map(fn (Plan $plan) => [$plan->label(), $plan->price()->amount], Plan::cases());

        $this->assertSame([
            ['1 bulan', 35_000],
            ['3 bulan', 99_000],
            ['9 bulan', 279_000],
            ['1 tahun', 349_000],
            ['2 tahun', 599_000],
        ], $prices);
    }

    public function test_months_are_added_without_spilling_into_the_next_month(): void
    {
        $this->assertSame('2026-02-28 10:00', Plan::OneMonth->addTo(new DateTimeImmutable('2026-01-31 10:00'))->format('Y-m-d H:i'));
        $this->assertSame('2028-02-29 10:00', Plan::NineMonths->addTo(new DateTimeImmutable('2027-05-31 10:00'))->format('Y-m-d H:i'));
        $this->assertSame('2027-07-03 10:00', Plan::NineMonths->addTo(new DateTimeImmutable('2026-10-03 10:00'))->format('Y-m-d H:i'));
    }

    public function test_a_store_that_never_paid_is_pending(): void
    {
        $this->assertSame(SubscriptionStatus::Pending, Subscription::reconstitute(1, null)->status(new DateTimeImmutable));
    }

    public function test_status_follows_the_end_date(): void
    {
        $subscription = Subscription::reconstitute(1, new DateTimeImmutable('2026-10-10 12:00'));

        $this->assertSame(SubscriptionStatus::Active, $subscription->status(new DateTimeImmutable('2026-10-10 11:59')));
        $this->assertSame(SubscriptionStatus::Expired, $subscription->status(new DateTimeImmutable('2026-10-10 12:00')));
    }

    public function test_renewing_early_extends_from_the_end_date(): void
    {
        $subscription = Subscription::reconstitute(1, new DateTimeImmutable('2026-10-10 12:00'));

        $endsAt = $subscription->extend(Plan::ThreeMonths, new DateTimeImmutable('2026-10-03 09:00'));

        $this->assertSame('2027-01-10 12:00', $endsAt->format('Y-m-d H:i'), 'the 7 days left are kept');
    }

    public function test_renewing_after_it_ran_out_starts_from_now(): void
    {
        $subscription = Subscription::reconstitute(1, new DateTimeImmutable('2026-09-01 12:00'));

        $endsAt = $subscription->extend(Plan::OneMonth, new DateTimeImmutable('2026-10-03 09:00'));

        $this->assertSame('2026-11-03 09:00', $endsAt->format('Y-m-d H:i'));
    }

    public function test_a_payment_keeps_the_price_of_its_plan(): void
    {
        $payment = Payment::start(1, Plan::OneYear, 'WRG-1', new DateTimeImmutable('+1 day'));

        $this->assertSame(349_000, $payment->amount()->amount);
        $this->assertSame(PaymentStatus::Pending, $payment->status());
        $this->assertFalse($payment->isPayable(new DateTimeImmutable), 'no checkout link yet');

        $payment->attachInvoice('inv-1', 'https://checkout.example/inv-1');
        $this->assertTrue($payment->isPayable(new DateTimeImmutable));
        $this->assertFalse($payment->isPayable(new DateTimeImmutable('+2 days')), 'past its expiry');
    }

    public function test_only_a_pending_payment_expires(): void
    {
        $payment = Payment::start(1, Plan::OneMonth, 'WRG-1', new DateTimeImmutable('+1 day'));
        $payment->markPaid(new DateTimeImmutable, new DateTimeImmutable('+1 month'));

        $payment->expire();

        $this->assertSame(PaymentStatus::Paid, $payment->status());
    }
}
