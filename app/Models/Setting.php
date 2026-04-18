<?php

namespace App\Models;

use App\Traits\LogsActivity; // 1. Import the Trait
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity; // 2. Add LogsActivity here

    protected $fillable = [
        'fiscal_year', 
        'budget_status', 
        'app_name', 
        'logo_path', 
        'state_name', 
        'currency_symbol',
        'allow_overspending'
    ];

    /**
     * GLOBAL HELPER: Get the current active settings row.
     * Usage: Setting::current();
     */
    public static function current()
    {
        return self::first() ?? self::create([
            'fiscal_year' => date('Y'),
            'app_name' => 'Budget Control System',
            'state_name' => 'Katsina State',
            'currency_symbol' => '₦',
        ]);
    }

    /**
     * CUSTOM LOG DESCRIPTION:
     * Specific tracking for administrative system-wide changes.
     */
    protected static function logAction($model, $action)
    {
        $overspending = $model->allow_overspending ? 'ENABLED' : 'DISABLED';
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'module' => 'System Settings',
            'description' => "{$action} System Config: Year {$model->fiscal_year}, Overspending: {$overspending}, App: {$model->app_name}",
            'ip_address' => request()->ip(),
        ]);
    }
}