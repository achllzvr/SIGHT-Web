<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $table = 'doctor_profile';
    protected $primaryKey = 'doctor_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'user_id',
        'phone',
        'clinic',
        'specialty',
        'location',
        'clinic_address',
        'operating_hours',
        'latitude',
        'longitude',
        'license_number',
        'is_validated',
        'is_partnered_directory_visible',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'is_partnered_directory_visible' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
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
