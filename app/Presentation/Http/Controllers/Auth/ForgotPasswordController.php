<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/** Step 1 of resetting a password: email a single-use link. */
class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('ForgotPassword', [
            'expireMinutes' => (int) config('auth.passwords.users.expire'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.'],
        );

        try {
            Password::sendResetLink($request->only('email'));
        } catch (TransportExceptionInterface $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => 'Email gagal dikirim. Periksa pengaturan SMTP atau coba lagi nanti.',
            ]);
        }

        // Same answer whether the address exists, is unknown or asked too often:
        // the form must not reveal which emails have an account.
        return back()->with('success', 'Kalau email itu terdaftar, tautan ganti password sudah dikirim. Cek juga folder spam.');
    }
}
