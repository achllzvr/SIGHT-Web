<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocument extends Model
{
    public const TYPE_TERMS = 'terms';
    public const TYPE_PRIVACY = 'privacy';

    protected $table = 'legal_documents';

    protected $fillable = [
        'document_type',
        'version_number',
        'content_text',
        'published_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function agreements(): HasMany
    {
        return $this->hasMany(UserLegalAgreement::class, 'legal_document_id');
    }
}
