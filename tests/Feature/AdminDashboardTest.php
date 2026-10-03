<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['store_id' => null, 'email' => 'admin@example.com']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function paid(Store $store, int $amount, string $paidAt): void
    {
        Payment::create([
            'store_id' => $store->id, 'plan' => 1, 'amount' => $amount, 'status' => 'paid',
            'external_id' => 'WRG-'.uniqid(), 'gateway' => 'fake', 'expires_at' => now(), 'paid_at' => $paidAt,
        ]);
    }

    public function test_store_owners_cannot_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_logs_in_to_the_dashboard_and_is_kept_off_store_pages(): void
    {
        $this->admin();

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/')->assertRedirect(route('admin.dashboard'));
        $this->get('/subscription')->assertRedirect(route('admin.dashboard'));
        $this->getJson('/api/products')->assertForbidden();
    }

    public function test_dashboard_lists_every_store_with_its_status_and_revenue(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $this->actingAs($this->admin());

        $active = User::factory()->for(Store::factory()->state(['name' => 'Warung Sari', 'subscription_ends_at' => now()->addDays(3)]))
            ->create(['email' => 'sari@example.com'])->store;
        User::factory()->for(Store::factory()->expired()->state(['name' => 'Warung Budi']))->create();
        User::factory()->for(Store::factory()->unpaid()->state(['name' => 'Warung Ani']))->create();

        $this->paid($active, 35_000, '2026-10-02 10:00:00');
        $this->paid($active, 99_000, '2026-09-20 10:00:00');

        $this->get('/admin')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('summary.stores', 3)
            ->where('summary.active', 1)
            ->where('summary.expired', 1)
            ->where('summary.pending', 1)
            ->where('summary.expiringSoon', 1)
            ->where('summary.revenueThisMonth', 35_000)
            ->where('summary.revenueTotal', 134_000)
            ->has('stores.data', 3)
            ->has('recentPayments', 2)
            ->where('recentPayments.0.store_name', 'Warung Sari'));

        $this->get('/admin?status=active')->assertInertia(fn ($page) => $page
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Warung Sari')
            ->where('stores.data.0.owner_email', 'sari@example.com')
            ->where('stores.data.0.status', 'active')
            ->where('stores.data.0.paid_total', 134_000)
            ->where('stores.data.0.paid_count', 2));

        $this->get('/admin?status=pending')->assertInertia(fn ($page) => $page
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Warung Ani'));
    }

    public function test_search_matches_store_name_and_owner_email(): void
    {
        $this->actingAs($this->admin());
        User::factory()->for(Store::factory()->state(['name' => 'Warung Sari']))->create(['email' => 'sari@example.com']);
        User::factory()->for(Store::factory()->state(['name' => 'Toko Budi']))->create(['email' => 'budi@mail.test']);

        $this->get('/admin?search=budi')->assertInertia(fn ($page) => $page
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Toko Budi'));

        $this->get('/admin?search=sari@example')->assertInertia(fn ($page) => $page
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Warung Sari'));
    }

    public function test_admin_create_command(): void
    {
        $this->artisan('admin:create', ['email' => 'Bos@Example.com'])
            ->expectsQuestion('Nama', 'Bos')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->assertSuccessful();

        $admin = User::firstWhere('email', 'bos@example.com');
        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->store_id);

        $this->artisan('admin:create', ['email' => 'bos@example.com'])
            ->expectsQuestion('Nama', 'Bos')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->assertFailed();
    }
}
