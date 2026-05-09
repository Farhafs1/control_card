<?php

namespace App\Models;

use App\Traits\LogsActivity; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Release extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',        
        'mda_id',
        'subhead_id', 
        'mda_code', 
        'subhead_code', 
        'release_date', 
        'reference_no', 
        'amount', 
        'narration',
        'quarter', 
        'year', // Added to support your comparative analysis logic
        'is_cancelled',
        'cancelled_reason'
    ];

    /**
     * The "booted" method of the model.
     * Automatically handles data integrity and automation logic.
     */
    protected static function booted()
    {
        static::creating(function ($release) {
            $date = $release->release_date ?? now();
            $carbonDate = Carbon::parse($date);

            // Automatically assign the quarter if missing
            if (!$release->quarter) {
                $release->quarter = ceil($carbonDate->month / 3);
            }

            // Automatically assign the year if missing
            if (!$release->year) {
                $release->year = $carbonDate->year;
            }
        });

        static::updating(function ($release) {
            // If the release_date is changed, we must re-calculate year and quarter
            if ($release->isDirty('release_date')) {
                $carbonDate = Carbon::parse($release->release_date);
                $release->quarter = ceil($carbonDate->month / 3);
                $release->year = $carbonDate->year;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Each release is posted by a specific User (Budget Officer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each release belongs to one subhead.
     */
    public function subhead(): BelongsTo
    {
        return $this->belongsTo(Subhead::class);
    }

    /**
     * Each release belongs to one MDA.
     */
    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * SCOPE: Only get active (not cancelled) releases.
     */
    public function scopeActive($query)
    {
        return $query->where('is_cancelled', false);
    }

    /**
     * SCOPE: Get releases created by the currently authenticated user.
     */
    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    */

    /**
     * High-detail logging for financial expenditures.
     */
    protected static function logAction($model, $action)
    {
        $statusPrefix = $model->is_cancelled ? '[CANCELLED] ' : '';
        $amountFormatted = number_format($model->amount, 2);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'module' => 'Expenditure',
            'description' => "{$statusPrefix}{$action} Release: ₦{$amountFormatted} (Ref: {$model->reference_no}) for Subhead: {$model->subhead_code}",
            'ip_address' => request()->ip(),
        ]);
    }
}