<?php

namespace App\Models;

use App\Traits\LogsActivity; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Subhead extends Model
{
    use HasFactory, LogsActivity; 

    protected $fillable = [
        'mda_id',
        'category_id',
        'mda_code',
        'subhead_code',
        'description',
        'fiscal_year', // <--- MAKE SURE THIS IS HERE
        'approved_provision',
        'additional_provision',
        'virement_provision',
        'supplementary_provision',
    ];

    protected $casts = [
        'approved_provision' => 'decimal:2',
        'additional_provision' => 'decimal:2',
    ];

    // --- NEW PERFORMANCE METRICS ---

    /**
     * Calculate performance percentage for the quarter.
     * Note: This relies on withSum('releases', 'amount') being called in the query.
     */
    // In Subhead.php
    public function getPerformancePercentageAttribute()
    {
        $provision = $this->total_provision; // Already cast to float in your model
        if ($provision <= 0) return 0;
        
        // Ensure we treat the string amount from the DB as a number
        $actual = (float) ($this->releases_sum_amount ?? 0); 
        
        return round(($actual / $provision) * 100, 2);
    }

    /**
     * Helper to get the total provision (Approved + Additional)
     */
    public function getTotalProvisionAttribute()
    {
        return (float)$this->approved_provision + (float)$this->additional_provision;
    }

    // --- EXISTING HELPER ---

    /**
     * Helper to get the total budget (Alias for total_provision)
     */
    public function getTotalBudgetAttribute()
    {
        return $this->total_provision;
    }

    // --- SCOPES & RELATIONSHIPS ---

    public function scopeByCodes(Builder $query, $mdaCode, $subheadCode)
    {
        $mdaCode = ltrim($mdaCode, '0');
        $subheadCode = ltrim($subheadCode, '0');

        return $query->where(function($q) use ($mdaCode) {
            $q->where('mda_code', $mdaCode)
              ->orWhere('mda_code', '0' . $mdaCode);
        })->where(function($q) use ($subheadCode) {
            $q->where('subhead_code', $subheadCode)
              ->orWhere('subhead_code', '0' . $subheadCode);
        });
    }

    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class, 'mda_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    // --- LOGGING ---

    protected static function logAction($model, $action)
    {
        $total = number_format($model->total_provision, 2);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'module' => 'Budget Subhead',
            'description' => "{$action} Subhead {$model->subhead_code} ({$model->description}). Total Provision: ₦{$total}",
            'ip_address' => request()->ip(),
        ]);
    }
}