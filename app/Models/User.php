<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The current schema does not consistently provide an updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'user';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'user_id';

    /**
     * Local dump schema uses manual integer PKs (no AUTO_INCREMENT).
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'role',
        'phone',
        'clinic',
        'location',
        'status',
        'must_change_password',
        'specialty',
        'license_number',
        'images',
        'failed_login_attempts',
        'locked_until',
        'email_verified_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
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
            'locked_until' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the password hash key.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    /**
     * Email address used for signed verification links.
     */
    public function getEmailForVerification(): string
    {
        return (string) $this->email;
    }

    /**
     * Check if user is a doctor
     */
    public function isDoctor(): bool
    {
        return strtolower((string) $this->role) === 'doctor';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return strtolower((string) $this->role) === 'admin';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return strtolower((string) $this->role) === strtolower($role);
    }

    /**
     * Canonical display name using split-name fields first.
     */
    public function getDisplayNameAttribute(): string
    {
        $first = trim((string) ($this->first_name ?? ''));
        $last = trim((string) ($this->last_name ?? ''));
        $combined = trim($first . ' ' . $last);

        return $combined !== '' ? $combined : 'Unnamed User';
    }

    /**
     * User initials for avatars.
     */
    public function getInitialsAttribute(): string
    {
        $first = trim((string) ($this->first_name ?? ''));
        $last = trim((string) ($this->last_name ?? ''));

        if ($first !== '' || $last !== '') {
            $a = $first !== '' ? strtoupper(substr($first, 0, 1)) : '';
            $b = $last !== '' ? strtoupper(substr($last, 0, 1)) : '';
            $initials = trim($a . $b);
            return $initials !== '' ? $initials : 'US';
        }

        return 'US';
    }

    /**
     * Doctor-specific metadata profile.
     */
    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class, 'user_id', 'user_id');
    }
}
