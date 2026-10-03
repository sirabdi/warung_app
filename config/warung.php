<?php

return [
    // Products with stock <= this number show up under "Stok Menipis" in the report.
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),

    'payment' => [
        // fake: local pay button, no account needed. xendit: Xendit Invoice
        // (keys in config/services.php; a test-mode key uses fake money).
        'driver' => env('PAYMENT_DRIVER', 'fake'),
    ],

    'subscription' => [
        // Reminder emails this many days before the subscription ends.
        'reminder_days' => [7, 1],

        // Registered but never paid: removed after this many hours (unless an
        // invoice is still open, which gets paid or expires first).
        'unpaid_store_hours' => 24,
    ],

    'registration' => [
        // OTP code: valid minutes, wrong tries allowed, seconds before resending.
        'code_minutes' => 10,
        'code_attempts' => 5,
        'resend_seconds' => 60,
    ],
];
