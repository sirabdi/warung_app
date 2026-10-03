<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Step 2: the link from the email lands here; a valid token sets a new password. */
class ChangePasswordController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->filled(['token', 'email'])) {
            return redirect()->route('password.request');
        }

        return Inertia::render('ChangePassword', [
            'token' => (string) $request->query('token'),
            'email' => (string) $request->query('email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Ulangi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        // The broker checks the token, its age (auth.passwords.users.expire)
        // and deletes it afterwards, so a link works once.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // hashed by the model cast
                    'remember_token' => Str::random(60), // log out "remember me" elsewhere
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Tautan sudah kedaluwarsa atau sudah dipakai. Minta tautan baru.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Password berhasil diganti. Silakan masuk dengan password baru.');
    }
}
