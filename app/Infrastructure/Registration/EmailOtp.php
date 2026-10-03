<?php

namespace App\Infrastructure\Registration;

use App\Infrastructure\Notification\RegistrationCodeNotification;
use App\Infrastructure\Persistence\Eloquent\Models\EmailVerification;
use Illuminate\Support\Facades\Notification;

/**
 * Proves that whoever registers owns the email address: a 6-digit code is sent
 * there and has to be typed back into the form.
 *
 * Only an HMAC of the code is stored. A code expires after a few minutes and
 * after a few wrong tries; a new one can be requested once a minute.
 */
final class EmailOtp
{
    /** A verified address has to be used within this time. */
    private const VERIFIED_MINUTES = 60;

    public function send(string $email): void
    {
        $email = self::normalize($email);

        $wait = $this->resendIn($email);
        if ($wait > 0) {
            throw new RegistrationRejected("Tunggu {$wait} detik sebelum minta kode baru.", 'email');
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $minutes = (int) config('warung.registration.code_minutes');

        // Mail first: when SMTP fails, no code is stored and no waiting starts.
        Notification::route('mail', $email)->notifyNow(new RegistrationCodeNotification($code, $minutes));

        EmailVerification::updateOrCreate(['email' => $email], [
            'code_hash' => $this->hash($email, $code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes($minutes),
            'verified_at' => null,
        ]);
    }

    /** Seconds until another code may be sent to this address. */
    public function resendIn(string $email): int
    {
        $sentAt = EmailVerification::where('email', self::normalize($email))->value('sent_at');

        if ($sentAt === null) {
            return 0;
        }

        $next = now()->parse($sentAt)->addSeconds((int) config('warung.registration.resend_seconds'));

        return max(0, (int) ceil(now()->diffInSeconds($next, false)));
    }

    public function verify(string $email, string $code): void
    {
        $email = self::normalize($email);
        $row = EmailVerification::firstWhere('email', $email);
        $maxAttempts = (int) config('warung.registration.code_attempts');

        if ($row === null) {
            throw new RegistrationRejected('Kirim kode verifikasi dulu.', 'code');
        }

        if ($row->expires_at->isPast()) {
            throw new RegistrationRejected('Kode sudah kedaluwarsa. Kirim kode baru.', 'code');
        }

        if ($row->attempts >= $maxAttempts) {
            throw new RegistrationRejected('Terlalu banyak kode salah. Kirim kode baru.', 'code');
        }

        if (! hash_equals($row->code_hash, $this->hash($email, trim($code)))) {
            $row->increment('attempts');
            $left = $maxAttempts - $row->attempts;

            throw new RegistrationRejected(
                $left > 0 ? "Kode salah. Sisa {$left} kali percobaan." : 'Terlalu banyak kode salah. Kirim kode baru.',
                'code',
            );
        }

        $row->update(['verified_at' => now()]);
    }

    public function isVerified(string $email): bool
    {
        return EmailVerification::where('email', self::normalize($email))
            ->where('verified_at', '>', now()->subMinutes(self::VERIFIED_MINUTES))
            ->exists();
    }

    public function forget(string $email): void
    {
        EmailVerification::where('email', self::normalize($email))->delete();
    }

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Bound to the address, so a code for one email never works for another. */
    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', $email.'|'.$code, (string) config('app.key'));
    }
}
