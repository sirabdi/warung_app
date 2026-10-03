<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Registration\EmailOtp;
use App\Infrastructure\Registration\RegisterStoreOwner;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Registration, in steps on one page:
 *   1. email → 6-digit code by email → code typed back (proves the address),
 *   2. owner, store and password → account created and logged in,
 * then SubscriptionController takes over (choose a plan, pay).
 *
 * The address being registered lives in the session, never in a form field
 * of step 2, so it cannot be swapped after it was verified.
 */
class RegisterController extends Controller
{
    private const SESSION_EMAIL = 'register.email';

    public function __construct(private readonly EmailOtp $otp) {}

    public function create(Request $request): Response
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        return Inertia::render('Register', [
            'email' => $email,
            'verified' => $email !== null && $this->otp->isVerified($email),
            'resendIn' => $email !== null ? $this->otp->resendIn($email) : 0,
            'codeMinutes' => (int) config('warung.registration.code_minutes'),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->merge(['email' => EmailOtp::normalize((string) $request->input('email'))]);
        $email = $request->validate(
            ['email' => ['required', 'email', 'max:255', 'unique:users,email']],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email ini sudah terdaftar. Silakan masuk, atau pakai "Lupa password".',
            ],
        )['email'];

        try {
            $this->otp->send($email);
        } catch (TransportExceptionInterface $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => 'Email gagal dikirim. Periksa pengaturan SMTP atau coba lagi nanti.',
            ]);
        }

        $request->session()->put(self::SESSION_EMAIL, $email);

        return back()->with('success', "Kode verifikasi dikirim ke {$email}. Cek juga folder spam.");
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $code = $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Kode wajib diisi.', 'code.digits' => 'Kode terdiri dari 6 angka.'],
        )['code'];

        $this->otp->verify($this->sessionEmail($request, 'code'), $code);

        return back()->with('success', 'Email terverifikasi. Lanjut isi data toko.');
    }

    /** "Ganti email": back to the start of step 1. */
    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_EMAIL);

        return redirect()->route('register');
    }

    public function store(Request $request, RegisterStoreOwner $registration): RedirectResponse
    {
        // "0812-3456 7890" and "+62 812…" are fine; only digits and + are kept.
        $request->merge(['phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(\+62|62|0)\d{8,13}$/'],
            'store_name' => ['required', 'string', 'max:100'],
            'store_address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon tidak valid, contoh 081234567890.',
            'store_name.required' => 'Nama toko wajib diisi.',
            'store_address.required' => 'Alamat toko wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $email = $this->sessionEmail($request, 'email');

        if (! $this->otp->isVerified($email)) {
            throw ValidationException::withMessages(['email' => 'Verifikasi email dulu.']);
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar. Silakan masuk.']);
        }

        $user = $registration->register([...$data, 'email' => $email]);

        $this->otp->forget($email);
        $request->session()->forget(self::SESSION_EMAIL);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('subscription.index')
            ->with('success', 'Akun berhasil dibuat. Pilih paket untuk mulai berjualan.');
    }

    private function sessionEmail(Request $request, string $field): string
    {
        return $request->session()->get(self::SESSION_EMAIL)
            ?? throw ValidationException::withMessages([$field => 'Isi email dan kirim kode verifikasi dulu.']);
    }
}
