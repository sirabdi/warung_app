<?php

namespace App\Infrastructure\Notification;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/** "Langganan berakhir dalam N hari", sent H-7 and H-1. */
class SubscriptionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $storeName,
        private readonly int $days,
        private readonly Carbon $endsAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->days === 1 ? 'besok' : "dalam {$this->days} hari";
        $date = $this->endsAt->locale('id')->translatedFormat('j F Y, H:i');

        return (new MailMessage)
            ->subject("Langganan {$this->storeName} berakhir {$when}")
            ->greeting("Halo, {$notifiable->name}")
            ->line("Langganan {$this->storeName} berakhir {$when}, pada {$date}.")
            ->line('Perpanjang sekarang tidak rugi: masa langganan baru ditambahkan setelah tanggal itu.')
            ->action('Perpanjang langganan', route('subscription.index'))
            ->line('Setelah berakhir, kasir terkunci sampai langganan diperpanjang. Datamu tetap aman.')
            ->salutation('Salam, '.config('app.name'));
    }
}
