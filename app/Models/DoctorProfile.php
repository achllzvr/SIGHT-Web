<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $table = 'doctor_profile';
    protected $primaryKey = 'doctor_id';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'user_id',
        'phone',
        'clinic',
        'specialty',
        'location',
        'license_number',
        'is_validated',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(PatientAccessLog::class, 'clinician_id', 'user_id');
    }
}
