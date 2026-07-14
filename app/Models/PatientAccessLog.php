<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAccessLog extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_EXPIRED = 'expired';

    public const ENDED_BY_GUARDIAN = 'guardian';
    public const ENDED_BY_CLINICIAN = 'clinician';
    public const ENDED_BY_SYSTEM = 'system';

    protected $table = 'patient_access_logs';

    protected $fillable = [
        'clinician_id',
        'child_id',
        'temporary_access_token_id',
        'accessed_at',
        'ended_at',
        'status',
        'ended_by',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinician_id', 'user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class, 'child_id', 'child_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(TemporaryAccessToken::class, 'temporary_access_token_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
