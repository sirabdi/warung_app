<?php

use App\Infrastructure\Subscription\RemoveUnpaidStores;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Subscription\SendSubscriptionReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:remind', function (SendSubscriptionReminders $reminders) {
    $this->info($reminders->run().' email pengingat dikirim.');
})->purpose('Email pengingat H-7 dan H-1 sebelum langganan berakhir');

Artisan::command('subscriptions:cleanup', function (RemoveUnpaidStores $cleanup) {
    ['expired' => $expired, 'removed' => $removed] = $cleanup->run();
    $this->info("{$expired} tagihan kedaluwarsa, {$removed} akun belum bayar dihapus.");
})->purpose('Tutup tagihan lewat waktu dan hapus akun yang tidak bayar dalam 24 jam');

Artisan::command('admin:create {email}', function (string $email) {
    $email = mb_strtolower(trim($email));
    $name = $this->ask('Nama', 'Admin');
    $password = $this->secret('Password (minimal 8 karakter)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8']],
    );
    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    // No store: an admin only sees /admin. is_admin is kept out of $fillable.
    User::create(['name' => $name, 'email' => $email, 'password' => $password, 'email_verified_at' => now()])
        ->forceFill(['is_admin' => true])
        ->save();

    $this->info("Admin {$email} dibuat. Masuk lewat /login, lalu buka /admin.");
})->purpose('Buat login admin untuk dashboard pemilik aplikasi');

// Needs `php artisan schedule:run` every minute from cron (locally: schedule:work).
Schedule::command('subscriptions:remind')->dailyAt('08:00');
Schedule::command('subscriptions:cleanup')->hourly();
