<?php

namespace Tests\Feature;

use App\Infrastructure\Notification\ResetPasswordNotification;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const SENT = 'Kalau email itu terdaftar, tautan ganti password sudah dikirim. Cek juga folder spam.';

    /** Ask for a link and pull the token out of the email that would be sent. */
    private function requestToken(User $user): string
    {
        $token = null;

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success', self::SENT);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user, &$token) {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith(url('/change-password?token='), $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $token = $query['token'];

            return $query['email'] === $user->email;
        });

        return $token;
    }

    public function test_the_pages_are_reachable_from_login(): void
    {
        $this->get('/forgot-password')->assertInertia(fn ($page) => $page
            ->component('ForgotPassword')
            ->where('expireMinutes', 10));

        // Without a token the change page sends you back to ask for one.
        $this->get('/change-password')->assertRedirect('/forgot-password');
        $this->get('/change-password?token=abc&email=a@b.c')->assertInertia(fn ($page) => $page
            ->component('ChangePassword')
            ->where('token', 'abc')
            ->where('email', 'a@b.c'));
    }

    public function test_a_password_can_be_reset_with_the_emailed_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'warung@example.com']);
        $token = $this->requestToken($user);

        $this->post('/change-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertRedirect('/login')->assertSessionHas('success');

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));

        $this->post('/login', ['email' => $user->email, 'password' => 'passwordbaru'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_link_works_only_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = $this->requestToken($user);
        $data = ['token' => $token, 'email' => $user->email, 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'];

        $this->post('/change-password', $data)->assertRedirect('/login');
        $this->post('/change-password', $data)->assertSessionHasErrors(['token' => 'Tautan sudah kedaluwarsa atau sudah dipakai. Minta tautan baru.']);
    }

    public function test_a_link_expires_after_ten_minutes(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = $this->requestToken($user);

        $this->travel(11)->minutes();

        $this->post('/change-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertSessionHasErrors('token');

        $this->assertFalse(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_unknown_emails_get_the_same_answer(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'tidak-ada@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', self::SENT);

        Notification::assertNothingSent();
    }

    public function test_the_new_password_is_validated(): void
    {
        $this->post('/change-password', [
            'token' => 'x', 'email' => 'a@b.c', 'password' => 'pendek', 'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['password']);

        $this->post('/forgot-password', ['email' => 'bukan-email'])->assertSessionHasErrors(['email' => 'Format email tidak valid.']);
    }
}
