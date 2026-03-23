<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingVerification extends Model
{
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
}