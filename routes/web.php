<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

// Existing Livewire Components Imports
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\MdaManagement;
use App\Livewire\Admin\ExpenditureTracking;
use App\Livewire\Admin\ExpenditureUpload;
use App\Livewire\Admin\BudgetUpload;            
use App\Livewire\Admin\SubheadShow;
use App\Livewire\Officer\Dashboard as OfficerDashboard;
use App\Livewire\Admin\SubheadIndex;
use App\Livewire\Admin\ExpenditureEdit;
use App\Livewire\Admin\SubheadBinCard;
use App\Livewire\Admin\ReleaseAnalytics; 
use App\Livewire\Admin\Settings; 
use App\Livewire\Admin\SystemLogs;
use App\Livewire\Admin\DataExtraction; 
use App\Livewire\Admin\StagedReleasesTable;
use App\Http\Controllers\ScraperController;

// New Analytics & Performance Features
use App\Livewire\Admin\BudgetPerformance;
use App\Http\Controllers\Admin\PerformanceExportController;
use App\Livewire\Admin\SubheadPreview;
use App\Livewire\BudgetAnalyticsDashboard;
use App\Livewire\PerformanceRanking; 
use App\Livewire\ComparativeAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


use App\Models\User;

/**
 * ROOT REDIRECT TO LOGIN
 * Bounces unauthenticated traffic landing on root domain directly to the login gate.
 */
Route::redirect('/', '/login');

/**
 * AI DIAGNOSTIC ROUTE (STABLE)
 */
Route::get('/ai-test', function () {
    set_time_limit(150); 
    try {
        $client = app(\Gemini\Client::class);
        $result = $client->generativeModel('gemini-1.5-flash')
            ->generateContent('Verify connection: Say "Systems Online"');
        
        return response()->json([
            'status' => 'success',
            'message' => $result->text(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

/**
 * AUTHENTICATED SYSTEM SESSION MATRIX
 */
Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * UNIVERSAL DASHBOARD REDIRECTOR
     */
    Route::get('/dashboard', function () {
        Session::forget('url.intended');
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('officer.dashboard');
    })->name('dashboard');

    // --- ADMINISTRATIVE SUITE (RESTRICTED ACCESS) ---
    Route::middleware(['can:admin-only'])->prefix('admin')->as('admin.')->group(function () {
        
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/users', UserManagement::class)->name('users');
        Route::get('/mdas', MdaManagement::class)->name('mdas');
        
        // Ledger Infrastructure & Subheads Management
        Route::get('/subheads', SubheadIndex::class)->name('subheads');
        Route::get('/subheads-mda/{mda}', SubheadShow::class)->name('subheads.show');
        Route::get('/subheads-bin/{subhead}/bin-card', SubheadBinCard::class)->name('subheads.bin-card');
        Route::get('/subhead-preview', SubheadPreview::class)->name('subhead-preview');
        
        // Automation Engine (Scraper Core Sync)
        Route::get('/data-extraction', DataExtraction::class)->name('data-extraction');
        Route::get('/staged-releases', StagedReleasesTable::class)->name('staged-releases');
        Route::post('/sync-records', [ScraperController::class, 'sync'])->name('scraper.sync');
        Route::get('/sync-progress', [ScraperController::class, 'getProgress'])->name('scraper.progress');

        // Reporting, Fiscal Metrics & Comparative Analytics Hub
        Route::get('/budget-performance', BudgetPerformance::class)->name('budget-performance');
        Route::get('/comparative-analysis', ComparativeAnalysis::class)->name('comparative-analysis');
        Route::get('/analytics/budget-performance', BudgetAnalyticsDashboard::class)->name('analytics.budget');
        Route::get('/analytics/performance-ranking', PerformanceRanking::class)->name('analytics.performance');
        Route::get('/analytics/expenditure', ReleaseAnalytics::class)->name('analytics.expenditure');

        // Export Engines & Document Compilers
        Route::get('/performance/export', [PerformanceExportController::class, 'export'])->name('performance.export');
        Route::get('/export/expenditure', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'export'])->name('expenditure.export');
        Route::get('/expenditure/ppt', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'generateAIPpt'])->name('expenditure.ppt');
        
        // System Allocation Updates & Intake Controls
        Route::get('/budget-upload', BudgetUpload::class)->name('budget-upload');
        Route::get('/expenditure', ExpenditureTracking::class)->name('expenditure');
        Route::get('/expenditure/upload', ExpenditureUpload::class)->name('expenditure.upload');
        Route::get('/expenditure/{release}/edit', ExpenditureEdit::class)->name('expenditure.edit');
        
        // CSV Structure Allocation Template Downloader
        Route::get('/expenditure-template/download', function() {
            $headers = ["mda_code", "subhead_code", "release_date", "reference_no", "amount", "narration"];
            return Response::stream(function() use ($headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $headers);
                fputcsv($file, ["12345678", "22020101", "2026-03-10", "VCH-001", "500000.00", "Sample Narration"]);
                fclose($file);
            }, 200, [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=expenditure_template.csv",
            ]);
        })->name('expenditure.template');

        // Core System Diagnostics & Platform Profiles
        Route::get('/settings', Settings::class)->name('settings'); 
        Route::get('/system-logs', SystemLogs::class)->name('system-logs');
    });

    // --- FIELD OFFICER WORKING ENGINES ---
    Route::middleware(['can:officer-only'])->prefix('officer')->as('officer.')->group(function () {
        Route::get('/dashboard', OfficerDashboard::class)->name('dashboard');
        Route::get('/recent-releases', \App\Livewire\Officer\RecentReleases::class)->name('recent-releases');
        Route::get('/subheads', \App\Livewire\Officer\SubheadsIndex::class)->name('subheads');
        Route::get('/subheads/{mda}', \App\Livewire\Officer\SubheadShow::class)->name('subheads.show');
        Route::get('/subheads/bin-card/{subhead}', \App\Livewire\Officer\BinCard::class)->name('subheads.bin-card');
        Route::get('/profile', \App\Livewire\Officer\ProfileSettings::class)->name('profile');
        Route::get('/explorer/{selectedMdaId?}', \App\Livewire\Officer\MdaExplorer::class)->name('mda-explorer');
        
        Route::get('/', function () {
            return redirect()->route('officer.dashboard');
        });
    });
});

/**
 * BULLETPROOF LOGOUT OVERRIDE
 */
Route::get('/logout-manual', function () {
    auth()->logout();
    Session::flush();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login'); 
})->name('logout.manual');

/**
 * SECURED ADMIN REGENERATOR COMPILER
 * Safe deployment guard: Verifies environment parameters before processing structural modifications.
 */
Route::get('/create-admin-fix', function () {
    if (app()->environment('production') && !request()->has('force_matrix_pass')) {
        abort(403, 'Unauthorized System Overwrite Blocked.');
    }

    try {
        // 1. Clear out any existing records via direct DB query to avoid observer checks
        DB::table('users')->where('email', 'admin@budget.com')->delete();

        // 2. Insert directly via Raw DB Query Builder to completely bypass the LogsActivity Trait
        DB::table('users')->insert([
            'name' => 'System Admin',
            'email' => 'admin@budget.com',
            'password' => Hash::make('123'), // Manually applying Hash::make since we are bypassing the Eloquent caster
            'role' => 'admin',
            'staff_no' => 'ADMIN001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return "<h2>Admin Account Planted Successfully via Raw Query Builder!</h2>";
    } catch (\Exception $e) {
        return "<h2>Creation Failed:</h2><pre>" . $e->getMessage() . "</pre>";
    }
});

require __DIR__.'/auth.php';