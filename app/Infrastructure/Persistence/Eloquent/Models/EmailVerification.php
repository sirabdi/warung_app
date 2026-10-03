<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/** An OTP sent from the registration form; see App\Infrastructure\Registration\EmailOtp. */
class EmailVerification extends Model
{
    protected $fillable = ['email', 'code_hash', 'attempts', 'sent_at', 'expires_at', 'verified_at'];

    protected $casts = [
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
