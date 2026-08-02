<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminProfile extends Model
{
    protected $table = 'admin_profile';
    protected $primaryKey = 'admin_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'user_id',
        'role_level',
        'last_login',
    ];

    protected $casts = [
        'last_login' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }
}
