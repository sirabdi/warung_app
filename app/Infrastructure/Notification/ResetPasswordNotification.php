<?php

namespace App\Infrastructure\Notification;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The "ganti password" email. Sent right away (not queued) so a broken SMTP
 * setting shows up as an error on the form instead of failing silently.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.users.expire');
        $url = url('/change-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));

        return (new MailMessage)
            ->subject('Ganti password '.config('app.name'))
            ->greeting("Halo, {$notifiable->name}")
            ->line('Ada permintaan untuk mengganti password akunmu.')
            ->action('Ganti password', $url)
            ->line("Tautan ini berlaku {$minutes} menit dan hanya bisa dipakai sekali.")
            ->line('Kalau kamu tidak memintanya, abaikan email ini — passwordmu tidak berubah.')
            ->salutation('Salam, '.config('app.name'));
    }
}
