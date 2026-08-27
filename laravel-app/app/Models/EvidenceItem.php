<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceItem extends Model
{
    protected $fillable = [
        'forensic_case_id', 'uploaded_by', 'original_filename', 'stored_filename',
        'file_type', 'size_bytes', 'sha256', 'md5', 'parse_result',
    ];

    protected function casts(): array
    {
        return ['parse_result' => 'array'];
    }

    public function forensicCase(): BelongsTo
    {
        return $this->belongsTo(ForensicCase::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Ambil daftar record hasil parsing (dipakai sebagai input analisis). */
    public function getRecordsAttribute(): array
    {
        return $this->parse_result['records'] ?? [];
    }
}
