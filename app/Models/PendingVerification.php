<?php

namespace App\Models;

use App\Traits\LogsActivity; // 1. Import the Trait
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingVerification extends Model
{
    use LogsActivity; // 2. Add LogsActivity here

    protected $fillable = [
        'mda_id',
        'subhead_id',
        'mda_code',
        'subhead_code',
        'release_date',
        'reference_no',
        'amount',
        'narration'
    ];

    /**
     * Relationship to the MDA
     */
    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class);
    }

    /**
     * Relationship to the Subhead
     */
    public function subhead(): BelongsTo
    {
        return $this->belongsTo(Subhead::class);
    }

    /**
     * CUSTOM LOG DESCRIPTION:
     * Specifically tracks the amount and reference number for pending items.
     */
    protected static function logAction($model, $action)
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'module' => 'Verification',
            'description' => "{$action} pending release (Ref: {$model->reference_no}) of ₦" . number_format($model->amount, 2),
            'ip_address' => request()->ip(),
        ]);
    }
}