<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Infrastructure\Notification\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'store_id' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /** The app owner: sees every store in /admin, runs none. Set with `php artisan admin:create`. */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Indonesian email that links to /change-password instead of Laravel's default. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
