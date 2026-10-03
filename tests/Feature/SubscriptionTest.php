<?php

namespace Tests\Feature;

use App\Infrastructure\Notification\SubscriptionReminderNotification;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(?callable $store = null): User
    {
        $factory = Store::factory();
        $user = User::factory()->for($store ? $store($factory) : $factory)->create();
        $this->actingAs($user);

        return $user;
    }

    /** Checkout with the fake gateway; returns the payment row. */
    private function checkout(int $plan): Payment
    {
        $response = $this->post('/subscription/checkout', ['plan' => $plan])->assertSessionHasNoErrors();
        $payment = Payment::latest('id')->first();
        $response->assertRedirect(route('payments.simulate', $payment->external_id));

        return $payment;
    }

    public function test_plans_are_offered_with_their_prices(): void
    {
        $this->owner(fn ($store) => $store->unpaid());

        $this->get('/subscription')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Subscription')
            ->where('status', 'pending')
            ->where('plans.2.label', '9 bulan')
            ->where('plans.2.price', 279_000)
            ->where('plans.4.saving', 35_000 * 24 - 599_000));
    }

    public function test_paying_activates_the_store(): void
    {
        $this->freezeSecond();
        $user = $this->owner(fn ($store) => $store->unpaid());

        $payment = $this->checkout(3);
        $this->assertSame(['pending', 99_000], [$payment->status, $payment->amount]);

        $this->get(route('payments.simulate', $payment->external_id))->assertOk();
        $this->post(route('payments.simulate.pay', $payment->external_id))
            ->assertRedirect(route('subscription.finish', $payment->external_id));

        $this->get(route('subscription.finish', $payment->external_id))
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertTrue($user->store->fresh()->subscription_ends_at->eq(now()->addMonths(3)));
        $this->get('/')->assertOk();
    }

    public function test_clicking_pay_twice_reuses_the_open_invoice(): void
    {
        $this->owner(fn ($store) => $store->unpaid());

        $first = $this->checkout(1);
        $second = $this->checkout(1);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payment::count());
    }

    public function test_renewing_early_keeps_the_days_left(): void
    {
        $this->freezeSecond();
        $user = $this->owner(fn ($store) => $store->state(['subscription_ends_at' => now()->addDays(5)]));

        $payment = $this->checkout(12);
        $this->post(route('payments.simulate.pay', $payment->external_id));

        $this->assertTrue($user->store->fresh()->subscription_ends_at->eq(now()->addDays(5)->addYear()));
    }

    public function test_an_expired_store_sees_the_renewal_page(): void
    {
        $this->owner(fn ($store) => $store->expired());

        $this->get('/')->assertRedirect('/subscription/expired');
        $this->get('/report')->assertRedirect('/subscription/expired');
        $this->getJson('/api/products')->assertStatus(402)->assertJsonPath('redirect', route('subscription.expired'));

        $this->get('/subscription/expired')->assertOk()->assertInertia(fn ($page) => $page->component('SubscriptionExpired'));
        $this->get('/subscription')->assertOk();
    }

    public function test_the_expired_page_is_only_for_expired_stores(): void
    {
        $this->owner();
        $this->get('/subscription/expired')->assertRedirect('/');
    }

    public function test_a_finish_page_of_another_store_is_hidden(): void
    {
        $this->owner(fn ($store) => $store->unpaid());
        $payment = $this->checkout(1);

        $this->owner();
        $this->get(route('subscription.finish', $payment->external_id))->assertNotFound();
    }

    public function test_the_simulation_is_off_with_a_real_gateway(): void
    {
        $this->owner(fn ($store) => $store->unpaid());
        $payment = $this->checkout(1);

        config(['warung.payment.driver' => 'xendit']);
        $this->post(route('payments.simulate.pay', $payment->external_id))->assertNotFound();
    }

    public function test_xendit_invoice_and_webhook(): void
    {
        $this->freezeSecond();
        config([
            'warung.payment.driver' => 'xendit',
            'services.xendit.secret_key' => 'xnd_development_test',
            'services.xendit.callback_token' => 'rahasia-callback',
        ]);
        Http::fake(['api.xendit.co/v2/invoices' => Http::response(['id' => 'inv_123', 'invoice_url' => 'https://checkout-staging.xendit.co/web/inv_123'])]);
        $user = $this->owner(fn ($store) => $store->unpaid());

        $this->post('/subscription/checkout', ['plan' => 9])->assertRedirect('https://checkout-staging.xendit.co/web/inv_123');

        $payment = Payment::first();
        Http::assertSent(fn ($request) => $request['external_id'] === $payment->external_id
            && $request['amount'] === 279_000
            && $request['currency'] === 'IDR'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('xnd_development_test:')));

        $callback = ['id' => 'inv_123', 'external_id' => $payment->external_id, 'status' => 'PAID', 'amount' => 279_000];

        $this->postJson('/webhooks/xendit/invoice', $callback)->assertUnauthorized();
        $this->postJson('/webhooks/xendit/invoice', $callback, ['x-callback-token' => 'salah'])->assertUnauthorized();

        $this->postJson('/webhooks/xendit/invoice', $callback, ['x-callback-token' => 'rahasia-callback'])->assertOk();
        $this->postJson('/webhooks/xendit/invoice', $callback, ['x-callback-token' => 'rahasia-callback'])->assertOk(); // retried

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($user->store->fresh()->subscription_ends_at->eq(now()->addMonths(9)), 'extended once, not twice');
    }

    public function test_xendit_webhook_rejects_a_wrong_amount_and_ignores_unknown_invoices(): void
    {
        config(['services.xendit.callback_token' => 'rahasia-callback']);
        $user = $this->owner(fn ($store) => $store->unpaid());
        $payment = $this->checkout(1);
        $headers = ['x-callback-token' => 'rahasia-callback'];

        $this->postJson('/webhooks/xendit/invoice', ['external_id' => $payment->external_id, 'status' => 'PAID', 'amount' => 1], $headers)
            ->assertJsonPath('status', 'rejected');
        $this->postJson('/webhooks/xendit/invoice', ['external_id' => 'tidak-ada', 'status' => 'PAID', 'amount' => 1], $headers)
            ->assertJsonPath('status', 'ignored');
        $this->postJson('/webhooks/xendit/invoice', ['external_id' => $payment->external_id, 'status' => 'EXPIRED'], $headers)
            ->assertOk();

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertNull($user->store->fresh()->subscription_ends_at);
    }

    public function test_reminders_go_out_seven_days_and_one_day_before(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 0));

        $inSeven = User::factory()->for(Store::factory()->state(['subscription_ends_at' => now()->addDays(7)->setTime(15, 0)]))->create();
        $tomorrow = User::factory()->for(Store::factory()->state(['subscription_ends_at' => now()->addDay()->setTime(9, 0)]))->create();
        $later = User::factory()->for(Store::factory()->state(['subscription_ends_at' => now()->addDays(10)]))->create();

        $this->artisan('subscriptions:remind')->expectsOutput('2 email pengingat dikirim.');
        $this->artisan('subscriptions:remind')->expectsOutput('0 email pengingat dikirim.');

        Notification::assertSentTo([$inSeven, $tomorrow], SubscriptionReminderNotification::class);
        Notification::assertNotSentTo($later, SubscriptionReminderNotification::class);
        Notification::assertCount(2);
    }

    public function test_unpaid_accounts_are_removed_after_a_day(): void
    {
        $old = User::factory()->for(Store::factory()->unpaid())->create();
        $this->actingAs($old);
        Category::create(['name' => 'Sembako']);

        $waiting = User::factory()->for(Store::factory()->unpaid())->create();
        $this->actingAs($waiting);
        $openInvoice = $this->checkout(1);

        $fresh = User::factory()->for(Store::factory()->unpaid())->create();
        $paying = User::factory()->create();

        $this->travel(25)->hours();
        Payment::whereKey($openInvoice->id)->update(['expires_at' => now()->addHour()]);
        Store::whereKey($fresh->store_id)->update(['created_at' => now()->subHours(2)]);

        $this->artisan('subscriptions:cleanup')->expectsOutput('0 tagihan kedaluwarsa, 1 akun belum bayar dihapus.');

        $this->assertModelMissing($old);
        $this->assertDatabaseMissing('categories', ['store_id' => $old->store_id]);
        $this->assertModelExists($waiting); // its invoice can still be paid
        $this->assertModelExists($fresh);
        $this->assertModelExists($paying);

        $this->travel(2)->hours();
        $this->artisan('subscriptions:cleanup')->expectsOutput('1 tagihan kedaluwarsa, 1 akun belum bayar dihapus.');
        $this->assertModelMissing($waiting);
    }
}
