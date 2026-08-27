<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'forensic_case_id', 'action', 'description', 'ip_address'];

    public static function record(string $action, ?string $description = null, ?int $caseId = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'forensic_case_id' => $caseId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
