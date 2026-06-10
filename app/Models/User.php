<?php

namespace App\Models;

use App\Traits\LogsActivity; // 1. Import the Trait
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, LogsActivity; // 2. Add LogsActivity here

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'staff_no',  // Added: BO1, BO2, etc.
        'role',      // Added: admin or officer
        'is_active', // Added: account status
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean', // Ensures 1/0 is always true/false
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A User (Budget Officer) can be assigned many MDAs.
     */
    // In app/Models/User.php
    // app/Models/User.php

    public function mdas(): HasMany
    {
        // Explicitly define the foreign key 'user_id'
        return $this->hasMany(Mda::class, 'user_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the user is an administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * CUSTOM LOG DESCRIPTION:
     * High-detail logging for user management and account security.
     */
    protected static function logAction($model, $action)
    {
        $status = $model->is_active ? 'Active' : 'Deactivated';
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1, // Logs who performed the action
            'action' => $action,
            'module' => 'User Management',
            'description' => "{$action} User Account: {$model->name} (Staff No: {$model->staff_no}) - Role: {$model->role}, Status: {$status}",
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * A User can be assigned many MDAs via a pivot table.
     * Make sure your pivot table is named 'mda_user' 
     * (or specify the table name in the second argument).
     */
    public function assignedMdas()
    {
        return $this->belongsToMany(Mda::class, 'mda_user', 'user_id', 'mda_id');
    }
}