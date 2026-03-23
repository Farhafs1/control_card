<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
            'app_name' => 'Budget Control System'
        ]);
    }
}