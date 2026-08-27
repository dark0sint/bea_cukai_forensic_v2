<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisResult extends Model
{
    protected $fillable = [
        'forensic_case_id', 'evidence_item_id', 'analysis_type', 'parameters', 'result', 'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'result' => 'array',
        ];
    }

    public function forensicCase(): BelongsTo
    {
        return $this->belongsTo(ForensicCase::class);
    }

    public function evidenceItem(): BelongsTo
    {
        return $this->belongsTo(EvidenceItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
