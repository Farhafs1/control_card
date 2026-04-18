<?php

namespace App\Models;

use App\Traits\LogsActivity; // 1. Import the Trait
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Mda extends Model
{
    use HasFactory, LogsActivity; // 2. Add LogsActivity here

    protected $fillable = [
        'user_id', 
        'mda_code', 
        'name', 
        'mda_secret_code', 
        'sector', 
        'is_active'
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['secret_code'];

    /**
     * VIRTUAL ATTRIBUTE: secret_code
     */
    protected function secretCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->mda_secret_code,
        );
    }

    /**
     * SCOPE: Find MDA by Code
     */
    public function scopeByCode(Builder $query, $code)
    {
        $code = ltrim($code, '0');
        return $query->where(function($q) use ($code) {
            $q->where('mda_code', $code)
              ->orWhere('mda_code', '0' . $code);
        });
    }

    /**
     * RELATIONSHIP: Each MDA belongs to one User (the Budget Officer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELATIONSHIP: An MDA has many budget subheads.
     */
    public function subheads(): HasMany
    {
        return $this->hasMany(Subhead::class, 'mda_id');
    }

    /**
     * RELATIONSHIP: An MDA has many categories (Personnel, Overhead, etc.)
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'mda_id');
    }

    /**
     * RELATIONSHIP: Tracking all expenditure releases for this MDA.
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'mda_id');
    }

    /**
     * CUSTOM LOG DESCRIPTION:
     * Overrides the default "Silent Logger" text for more detail.
     */
    protected static function logAction($model, $action)
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'module' => 'MDA',
            'description' => "{$action} MDA: {$model->name} (Code: {$model->mda_code})",
            'ip_address' => request()->ip(),
        ]);
    }
}