<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
// Existing Imports
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

// NEW FEATURES
use App\Livewire\Admin\BudgetPerformance;
use App\Http\Controllers\Admin\PerformanceExportController;
use App\Livewire\Admin\SubheadPreview;
use App\Livewire\BudgetAnalyticsDashboard;
use App\Livewire\PerformanceRanking; // Make sure to import the component
use App\Livewire\ComparativeAnalysis;

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

    // --- ADMIN SECTION ---
    Route::middleware(['can:admin-only'])->prefix('admin')->as('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/users', UserManagement::class)->name('users');
        Route::get('/mdas', MdaManagement::class)->name('mdas');
        
        // Subheads & Ledger
        Route::get('/subheads', SubheadIndex::class)->name('subheads');
        Route::get('/subheads-mda/{mda}', SubheadShow::class)->name('subheads.show');
        Route::get('/subheads-bin/{subhead}/bin-card', SubheadBinCard::class)->name('subheads.bin-card');
        
        // --- DATA EXTRACTION & SYNC ---
        Route::get('/data-extraction', DataExtraction::class)->name('data-extraction');
        Route::get('/staged-releases', StagedReleasesTable::class)->name('staged-releases');

        // --- FISCAL PERFORMANCE & REPORTING ---
        // The Main Performance Dashboard
        Route::get('/budget-performance', BudgetPerformance::class)->name('budget-performance');

        // The new futuristic Comparative Analytics hub
        // Route::get('/analytics/comparative', ComparativeAnalysis::class)->name('analytics.comparative');
        Route::get('/comparative-analysis', ComparativeAnalysis::class)->name('comparative-analysis');

        // The Export Engine (PDF/CSV)
        Route::get('/performance/export', [PerformanceExportController::class, 'export'])->name('performance.export');
        
        // Legacy Analytics
        Route::get('/analytics/expenditure', ReleaseAnalytics::class)->name('analytics.expenditure');

        // Budget Upload
        Route::get('/budget-upload', BudgetUpload::class)->name('budget-upload');

        // Expenditure Management
        Route::get('/expenditure', ExpenditureTracking::class)->name('expenditure');
        Route::get('/expenditure/upload', ExpenditureUpload::class)->name('expenditure.upload');
        Route::get('/expenditure/{release}/edit', ExpenditureEdit::class)->name('expenditure.edit');
        
        // Legacy Exports
        // --- UPDATED EXPORTS ---
        // Pointing to AnalyticsExportController as requested by your component logic
        Route::get('/export/expenditure', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'export'])
            ->name('expenditure.export'); // Removed the .pdf suffix to match the component

        Route::get('/expenditure/ppt', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'generateAIPpt'])
            ->name('expenditure.ppt');
        
        // Template Downloader
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

        Route::get('/settings', Settings::class)->name('settings'); 
        Route::get('/system-logs', SystemLogs::class)->name('system-logs');
        
        Route::post('/sync-records', [ScraperController::class, 'sync'])->name('scraper.sync');
        Route::get('/sync-progress', [ScraperController::class, 'getProgress'])->name('scraper.progress');

        // Inside the admin group...
        Route::get('/subhead-preview', SubheadPreview::class)->name('subhead-preview');
        Route::get('/analytics/budget-performance', BudgetAnalyticsDashboard::class)
        ->name('analytics.budget');
        Route::get('/analytics/performance-ranking', PerformanceRanking::class)
        ->name('analytics.performance');

    });

    // --- OFFICER SECTION ---
    Route::middleware(['can:officer-only'])->prefix('officer')->as('officer.')->group(function () {
        Route::get('/dashboard', OfficerDashboard::class)->name('dashboard');
        Route::get('/recent-releases', \App\Livewire\Officer\RecentReleases::class)->name('recent-releases');
        Route::get('/subheads', \App\Livewire\Officer\SubheadsIndex::class)->name('subheads');
        Route::get('/subheads/{mda}', \App\Livewire\Officer\SubheadShow::class)->name('subheads.show');
        Route::get('/subheads/bin-card/{subhead}', \App\Livewire\Officer\BinCard::class)->name('subheads.bin-card');
        Route::get('/profile', \App\Livewire\Officer\ProfileSettings::class)->name('profile');
        
        Route::get('/', function () {
            return redirect()->route('officer.dashboard');
        });

        Route::get('/explorer/{selectedMdaId?}', \App\Livewire\Officer\MdaExplorer::class)->name('mda-explorer');
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

require __DIR__.'/auth.php';