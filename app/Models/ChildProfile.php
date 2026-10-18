<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildProfile extends Model
{
    protected $table = 'child_profile';
    protected $primaryKey = 'child_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'birthdate',
        'device_id',
        'last_sync',
        'login_code',
        'calibration_baseline',
        'fcm_token',
        'updated_at',
    ];

    protected $casts = [
        'last_sync' => 'datetime',
        'updated_at' => 'datetime',
        'calibration_baseline' => 'json',
        'birthdate' => 'date',
    ];

    /** Chronological age in whole years from birthdate. */
    public function chronologicalAgeYears(): ?int
    {
        if (empty($this->birthdate)) {
            return null;
        }

        return $this->birthdate instanceof \Carbon\CarbonInterface
            ? $this->birthdate->age
            : \Carbon\Carbon::parse((string) $this->birthdate)->age;
    }

    /** Age-based max daily Watch Area minutes: 3–5→60, 6–8→90, 9–12+→120. */
    public function ageMaxDailyMinutes(): int
    {
        $age = $this->chronologicalAgeYears();
        if ($age === null) {
            return 120;
        }
        if ($age <= 5) {
            return 60;
        }
        if ($age <= 8) {
            return 90;
        }

        return 120;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guardians()
    {
        return $this->belongsToMany(GuardianProfile::class, 'guardian_child_link', 'child_id', 'guardian_id');
    }

    public function eyeHealthMetrics()
    {
        return $this->hasMany(EyeHealthMetrics::class, 'child_id');
    }

    public function eyeHealthScores()
    {
        return $this->hasMany(EyeHealthScore::class, 'child_id');
    }

    public function sessionLimits()
    {
        return $this->hasOne(SessionLimits::class, 'child_id');
    }

    public function inventory()
    {
        return $this->hasMany(ChildInventory::class, 'child_id');
    }

    public function pet()
    {
        return $this->hasOne(VirtualPet::class, 'child_id');
    }

    public function temporaryAccessTokens()
    {
        return $this->hasMany(TemporaryAccessToken::class, 'child_id', 'child_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(PatientAccessLog::class, 'child_id', 'child_id');
    }
}
