<?php

namespace Tests\Feature;

use App\Application\Category\DefaultCategories;
use App\Infrastructure\Notification\RegistrationCodeNotification;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const FORM = [
        'name' => 'Siti Aminah',
        'phone' => '0812-3456-7890',
        'store_name' => 'Warung Bu Siti',
        'store_address' => 'Jl. Melati No. 5, Bandung',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function sendCode(string $email = 'siti@example.com'): string
    {
        $this->post('/register/code', ['email' => $email])->assertSessionHasNoErrors();

        $code = null;
        Notification::assertSentOnDemand(RegistrationCodeNotification::class, function ($notification, $channels, AnonymousNotifiable $to) use (&$code, $email) {
            $code = $notification->code;

            return $to->routes['mail'] === strtolower(trim($email));
        });

        return $code;
    }

    public function test_the_page_is_reachable_from_login(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk()->assertInertia(fn ($page) => $page->component('Register')->where('email', null));
    }

    public function test_a_new_customer_registers_and_is_sent_to_choose_a_plan(): void
    {
        $code = $this->sendCode('Siti@Example.com ');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->post('/register/verify', ['code' => $code])->assertSessionHasNoErrors();
        $this->get('/register')->assertInertia(fn ($page) => $page->where('email', 'siti@example.com')->where('verified', true));

        $this->post('/register', self::FORM)->assertRedirect('/subscription');

        $user = User::firstWhere('email', 'siti@example.com');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(['Warung Bu Siti', '081234567890', 'Jl. Melati No. 5, Bandung', null], [
            $user->store->name, $user->store->phone, $user->store->address, $user->store->subscription_ends_at,
        ]);
        $this->assertSame(count(DefaultCategories::NAMES), Category::where('store_id', $user->store_id)->count());
        $this->assertDatabaseMissing('email_verifications', ['email' => 'siti@example.com']);

        // Not paid yet: the till sends them back to the plans.
        $this->get('/')->assertRedirect('/subscription');
        $this->getJson('/api/products')->assertStatus(402);
    }

    public function test_the_details_step_needs_a_verified_email(): void
    {
        $this->post('/register', self::FORM)->assertSessionHasErrors('email');

        $this->sendCode();
        $this->post('/register', self::FORM)->assertSessionHasErrors(['email' => 'Verifikasi email dulu.']);
        $this->assertSame(0, Store::count());
    }

    public function test_a_wrong_code_is_limited_to_five_tries(): void
    {
        $code = $this->sendCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->post('/register/verify', ['code' => $wrong])->assertSessionHasErrors(['code' => 'Kode salah. Sisa 4 kali percobaan.']);
        foreach (range(1, 4) as $_) {
            $this->post('/register/verify', ['code' => $wrong]);
        }

        $this->post('/register/verify', ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Terlalu banyak kode salah. Kirim kode baru.']);
    }

    public function test_a_code_expires_and_a_new_one_waits_a_minute(): void
    {
        $code = $this->sendCode();

        $this->post('/register/code', ['email' => 'siti@example.com'])->assertSessionHasErrors('email');

        $this->travel(11)->minutes();
        $this->post('/register/verify', ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Kode sudah kedaluwarsa. Kirim kode baru.']);

        $this->assertNotSame('', $this->sendCode(), 'after the wait a new code can be sent');
    }

    public function test_a_registered_email_cannot_register_again(): void
    {
        User::factory()->create(['email' => 'siti@example.com']);

        $this->post('/register/code', ['email' => 'siti@example.com'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_the_store_details_are_validated(): void
    {
        $this->post('/register/verify', ['code' => $this->sendCode()]);

        $this->post('/register', [...self::FORM, 'phone' => '12ab', 'store_name' => '', 'password_confirmation' => 'lain'])
            ->assertSessionHasErrors(['phone', 'store_name', 'password']);
    }

    public function test_changing_the_email_starts_over(): void
    {
        $this->post('/register/verify', ['code' => $this->sendCode()]);

        $this->post('/register/restart')->assertRedirect('/register');
        $this->get('/register')->assertInertia(fn ($page) => $page->where('email', null)->where('verified', false));
    }
}
