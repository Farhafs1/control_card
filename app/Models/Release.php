<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Release extends Model
{
    use HasFactory;

    protected $fillable = [
        'mda_id',           // ADDED: This fixes the NOT NULL error
        'subhead_id', 
        'mda_code', 
        'subhead_code', 
        'release_date', 
        'reference_no', 
        'amount', 
        'narration', 
        'is_cancelled',
        'cancelled_reason'
    ];

    /**
     * RELATIONSHIP: Each release belongs to one subhead
     */
    public function subhead(): BelongsTo
    {
        return $this->belongsTo(Subhead::class);
    }

    /**
     * RELATIONSHIP: Each release belongs to one MDA
     */
    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class);
    }

    /**
     * SCOPE: Only get active (not cancelled) releases
     * Usage: Release::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where('is_cancelled', false);
    }
}