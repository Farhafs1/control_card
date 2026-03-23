<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\MdaManagement;
use App\Livewire\Admin\ExpenditureTracking;
use App\Livewire\Admin\ExpenditureUpload;
use App\Livewire\Admin\BudgetUpload;               
use App\Livewire\Admin\SubheadShow;
use App\Livewire\Officer\Dashboard as OfficerDashboard;
use App\Http\Controllers\Admin\ExportController;
use App\Livewire\Admin\SubheadIndex;
use App\Livewire\Admin\ExpenditureEdit;
use App\Livewire\Admin\SubheadBinCard;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('officer.dashboard');
    })->name('dashboard');

    // ADMIN SECTION
    Route::middleware(['can:admin-only'])->prefix('admin')->as('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/users', UserManagement::class)->name('users');
        Route::get('/mdas', MdaManagement::class)->name('mdas');
        
        // Subheads
        Route::get('/subheads', SubheadIndex::class)->name('subheads');
        Route::get('/subheads/{mda}', SubheadShow::class)->name('subheads.show');
        Route::get('/subheads/{subhead}/bin-card', SubheadBinCard::class)->name('subheads.bin-card');
        
        // Budget
        Route::get('/budget-upload', BudgetUpload::class)->name('budget-upload');

        // Expenditure
        Route::get('/expenditure', ExpenditureTracking::class)->name('expenditure');
        Route::get('/expenditure/upload', ExpenditureUpload::class)->name('expenditure.upload');
        Route::get('/expenditure/pdf', [ExportController::class, 'expenditure'])->name('expenditure.pdf');
        Route::get('/expenditure/{release}/edit', ExpenditureEdit::class)->name('expenditure.edit');
        
        // Template Downloader
        Route::get('/expenditure/template', function() {
            $headers = ["mda_code", "subhead_code", "release_date", "reference_no", "amount", "narration"];
            return response()->stream(function() use ($headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $headers);
                fputcsv($file, ["12345678", "22020101", "2026-03-10", "VCH-001", "500000.00", "Sample Narration"]);
                fclose($file);
            }, 200, [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=expenditure_template.csv",
            ]);
        })->name('expenditure.template');
    });

    // OFFICER SECTION
    Route::middleware(['can:officer-only'])->prefix('officer')->as('officer.')->group(function () {
        Route::get('/dashboard', OfficerDashboard::class)->name('dashboard');
    });
});

require __DIR__.'/auth.php';