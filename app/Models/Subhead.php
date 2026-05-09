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
        'approved_provision'      => 'decimal:2',
        'additional_provision'    => 'decimal:2',
        'virement_provision'      => 'decimal:2',   // Add this
        'supplementary_provision' => 'decimal:2',   // Add this
        'fiscal_year'             => 'integer',
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
        return (float)$this->approved_provision 
            + (float)$this->additional_provision 
            + (float)$this->supplementary_provision 
            + (float)$this->virement_provision; // Virement can be negative, so + handles both cases
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

    // app/Models/Subhead.php

    /**
     * Scope a query to filter by GFSM category based on subhead_code strings.
     */
    public function scopeOfCategory($query, $categoryType)
    {
        if (!$categoryType) {
            return $query;
        }

        return $query->where(function ($q) use ($categoryType) {
            // 1. Capital Expenditure (10-digit rule)
            if ($categoryType === 'Expenditure_Capital') {
                $q->whereRaw('LENGTH(subhead_code) = 10');
            } 
            
            // 2. Personnel (Prefix 21, not 10 digits)
            elseif ($categoryType === 'Expenditure_Personnel') {
                $q->where('subhead_code', 'like', '21%')
                ->whereRaw('LENGTH(subhead_code) != 10');
            }

            // 3. Overhead (Prefix 22, not 10 digits)
            elseif ($categoryType === 'Expenditure_Overhead') {
                $q->where('subhead_code', 'like', '22%')
                ->whereRaw('LENGTH(subhead_code) != 10');
            }

            // 4. Revenue Categories (8-digit rule + specific prefixes)
            elseif (str_starts_with($categoryType, 'Revenue_')) {
                $prefix = match($categoryType) {
                    'Revenue_FAAC'            => '11',
                    'Revenue_IGR'             => '12',
                    'Revenue_Aid_Grant'       => '13',
                    'Revenue_Capital_Receipt' => '14',
                    default                   => null
                };

                if ($prefix) {
                    $q->where('subhead_code', 'like', $prefix . '%')
                    ->whereRaw('LENGTH(subhead_code) = 8');
                }
            }
        });
    }
}