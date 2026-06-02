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
        'year',
        'is_cancelled',
        'cancelled_reason'
    ];

    /**
     * Explicit structural casting forces Eloquent to read/write columns cleanly.
     */
    protected $casts = [
        'release_date' => 'date', 
        'amount'       => 'decimal:2',
        'is_cancelled' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     * Automatically handles data integrity and automation logic.
     */
    protected static function booted()
    {
        // The 'saving' event handles both creating and updating systematically
        static::saving(function ($release) {
            if ($release->release_date) {
                // Parse safely whether it is currently a Carbon object or a raw string from a file
                $date = Carbon::parse($release->release_date);
                
                // Set the absolute single source of truth for dashboard metrics
                $release->year = $date->year;
                $release->quarter = $date->quarter; // Returns 1-4 natively
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subhead(): BelongsTo
    {
        return $this->belongsTo(Subhead::class);
    }

    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_cancelled', false);
    }

    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    */

    protected static function logAction($model, $action)
    {
        $statusPrefix = $model->is_cancelled ? '[CANCELLED] ' : '';
        $amountFormatted = number_format($model->amount, 2);
        
        // Dynamic determination of author identity
        $userId = auth()->id();
        $executorType = "User ID: " . ($userId ?? 'System');
        
        if (app()->runningInConsole()) {
            $executorType = '[CRON/CLI ENGINE]';
        }

        \App\Models\ActivityLog::create([
            'user_id' => $userId ?? 1, // Fallback safely to admin record account anchor
            'action' => $action,
            'module' => 'Expenditure',
            'description' => "{$statusPrefix}{$action} by {$executorType} Release: ₦{$amountFormatted} (Ref: {$model->reference_no}) for Subhead: {$model->subhead_code}",
            'ip_address' => app()->runningInConsole() ? '127.0.0.1' : request()->ip(),
        ]);
    }
}