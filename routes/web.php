<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
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
use App\Livewire\Admin\StagedReleasesTable; // <--- UPDATED: Corrected class name
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\ScraperController;

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
        
        // --- NEW: AUTOMATED INTELLIGENCE ---
        Route::get('/data-extraction', DataExtraction::class)->name('data-extraction');
        // Pointing the 'staged-releases' route name to the 'StagedReleasesTable' class
        Route::get('/staged-releases', StagedReleasesTable::class)->name('staged-releases');

        // Analytics & AI Reporting
        Route::get('/analytics/expenditure', ReleaseAnalytics::class)->name('analytics.expenditure');

        // Budget
        Route::get('/budget-upload', BudgetUpload::class)->name('budget-upload');

        // Expenditure Management
        Route::get('/expenditure', ExpenditureTracking::class)->name('expenditure');
        Route::get('/expenditure/upload', ExpenditureUpload::class)->name('expenditure.upload');
        Route::get('/expenditure/{release}/edit', ExpenditureEdit::class)->name('expenditure.edit');
        
        // Exports
        Route::get('/export/expenditure', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'export'])->name('expenditure.export.pdf');
        Route::get('/expenditure/ppt', [App\Http\Controllers\Admin\AnalyticsExportController::class, 'generateAIPpt'])->name('expenditure.ppt');
        
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
        
        // The route the button clicks
        Route::post('/sync-records', [ScraperController::class, 'sync'])->name('scraper.sync');

        // The route the progress bar pings
        Route::get('/sync-progress', [ScraperController::class, 'getProgress'])->name('scraper.progress');
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