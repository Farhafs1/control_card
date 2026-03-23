<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Subhead extends Model
{
    use HasFactory;

    protected $fillable = [
        'mda_id', 
        'mda_code', 
        'category_id', 
        'subhead_code', 
        'description', 
        'approved_provision', 
        'additional_provision'
    ];

    protected $casts = [
        'approved_provision' => 'decimal:2',
        'additional_provision' => 'decimal:2',
    ];

    /**
     * Helper to get the total budget (Original + Supplementary)
     */
    public function getTotalBudgetAttribute()
    {
        // Added float cast to ensure math works even if values are null
        return (float)$this->approved_provision + (float)$this->additional_provision;
    }

    /**
     * SCOPE: Flexible Code Search
     */
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

    /**
     * RELATIONSHIP: Each subhead belongs to a specific MDA.
     */
    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class, 'mda_id');
    }

    /**
     * RELATIONSHIP: Each subhead belongs to a category.
     * This is what drives the color coding.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * RELATIONSHIP: A subhead has many expenditure releases.
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }
}