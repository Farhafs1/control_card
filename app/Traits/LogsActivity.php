<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    // Laravel automatically calls any method starting with "boot" + TraitName
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logAction($model, 'Created');
        });

        static::updated(function ($model) {
            self::logAction($model, 'Updated');
        });

        static::deleted(function ($model) {
            self::logAction($model, 'Deleted');
        });
    }

    protected static function logAction($model, $action)
    {
        // Get the name of the model (e.g., "Expenditure" or "Budget")
        $moduleName = class_basename($model);
        
        ActivityLog::create([
            'user_id' => Auth::id() ?? 1, // Default to user 1 if not logged in (e.g. console)
            'action' => $action,
            'module' => $moduleName,
            'description' => "{$action} {$moduleName} entry (ID: {$model->id})",
            'ip_address' => request()->ip(),
        ]);
    }
}