<?php

namespace App\Models;

use App\Traits\LogsActivity; // 1. Import the Trait
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use LogsActivity; // 2. Use the Trait

    protected $fillable = ['mda_id', 'type'];

    /**
     * RELATIONSHIP: Category belongs to an MDA
     */
    public function mda(): BelongsTo
    {
        return $this->belongsTo(Mda::class);
    }

    /**
     * RELATIONSHIP: Category has many Subheads
     * This is the link we discussed!
     */
    public function subheads(): HasMany
    {
        return $this->hasMany(Subhead::class);
    }
}