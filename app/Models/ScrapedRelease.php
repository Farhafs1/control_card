<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ScrapedRelease extends Model
{
    protected $fillable = [
        'reference_no', 
        'mda_code', 
        'mda_name',     // ADDED: So you can update the name via the modal
        'subhead_code', 
        'release_date', 
        'narration', 
        'amount', 
        'is_salary', 
        'batch_id',
        'status'        // ADDED: To support Approved, Circulating, Returned filters
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'release_date' => 'date:Y-m-d',
        'amount'       => 'float',
        'is_salary'    => 'boolean',
    ];

    /**
     * Scope a query to only include releases of a certain status.
     * This makes your Livewire render() method much cleaner.
     */
    public function scopeStatus(Builder $query, $status): Builder
    {
        if (!$status || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Check if this record already exists in the PERMANENT table
     * to prevent duplicate approvals.
     */
    public function existsInMainLedger()
    {
        // Using the relationship check you provided
        return Release::where('reference_no', $this->reference_no)
            ->where('amount', $this->amount) // Adding amount check for better accuracy
            ->whereHas('mda', fn($q) => $q->where('mda_code', $this->mda_code))
            ->whereHas('subhead', fn($q) => $q->where('subhead_code', $this->subhead_code))
            ->exists();
    }
}