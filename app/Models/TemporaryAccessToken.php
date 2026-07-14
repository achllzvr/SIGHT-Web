<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TemporaryAccessToken extends Model
{
    protected $table = 'temporary_access_tokens';

    protected $fillable = [
        'child_id',
        'token_code',
        'qr_payload',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class, 'child_id', 'child_id');
    }

    public function accessLog(): HasOne
    {
        return $this->hasOne(PatientAccessLog::class, 'temporary_access_token_id');
    }

    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }
}
