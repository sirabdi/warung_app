<?php

namespace App\Infrastructure\Notification;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The 6-digit code of the registration form. Sent right away, like the reset email. */
class RegistrationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        private readonly int $minutes,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->code} adalah kode verifikasi ".config('app.name'))
            ->greeting('Halo,')
            ->line('Masukkan kode ini di formulir pendaftaran:')
            ->line("**{$this->code}**")
            ->line("Kode berlaku {$this->minutes} menit. Jangan berikan kode ini kepada siapa pun.")
            ->line('Kalau kamu tidak sedang mendaftar, abaikan email ini.')
            ->salutation('Salam, '.config('app.name'));
    }
}
