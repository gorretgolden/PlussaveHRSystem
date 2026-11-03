<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'username', 'email', 'employee_id', 'role', 'password', 'active', 'must_change_password',
    'failed_login_attempts', 'locked_until', 'last_login_at', 'last_login_ip', 'last_activity_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'locked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function lockMinutesLeft(): int
    {
        return $this->isLocked() ? max(1, (int) ceil(now()->diffInSeconds($this->locked_until, true) / 60)) : 0;
    }

    public function recordFailedLogin(): void
    {
        $max = max(1, (int) Setting::get('max_login_attempts', 5));
        $minutes = max(1, (int) Setting::get('lockout_duration_minutes', 15));

        $this->failed_login_attempts++;

        if ($this->failed_login_attempts >= $max) {
            $this->locked_until = now()->addMinutes($minutes);
        }

        $this->save();
    }

    public function recordSuccessfulLogin(?string $ip = null): void
    {
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_activity_at' => now(),
        ])->save();
    }

    public function unlock(): void
    {
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function attemptsLeft(): int
    {
        return max(0, max(1, (int) Setting::get('max_login_attempts', 5)) - $this->failed_login_attempts);
    }
}
