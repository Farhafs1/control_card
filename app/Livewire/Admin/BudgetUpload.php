<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\Category;
use App\Models\Subhead;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema; // <--- ADD THIS LINE

class BudgetUpload extends Component
{
    use WithFileUploads;

    public $budget_file;
    public $confirm_truncate = false;

    /**
     * Export the entire Subhead table to CSV for Backup
     */
    public function exportBudget()
    {
        if (auth()->user()->role !== 'admin') return;

        $fileName = 'Budget_Backup_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'MDA_CODE', 'MDA_NAME', 'CATEGORY', 'SUBHEAD_CODE', 
            'DESCRIPTION', 'APPROVED_PROVISION', 'ADDITIONAL_PROVISION', 'TOTAL'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Updated to include mda_code from the subhead table itself
            Subhead::with(['mda', 'category'])->chunk(500, function($subheads) use ($file) {
                foreach ($subheads as $subhead) {
                    fputcsv($file, [
                        $subhead->mda_code, // Direct from subhead table
                        $subhead->mda->name ?? 'N/A',
                        $subhead->category->type ?? 'N/A',
                        $subhead->subhead_code,
                        $subhead->description,
                        $subhead->approved_provision,
                        $subhead->additional_provision,
                        $subhead->total_budget 
                    ]);
                }
            });

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function truncateBudget()
    {
        if (auth()->user()->role !== 'admin') return;

        // This works for both MySQL and SQLite in Laravel
        Schema::disableForeignKeyConstraints();
        
        Subhead::truncate();
        Category::truncate();
        
        Schema::enableForeignKeyConstraints();

        $this->dispatch('swal:toast', 'System wiped: All budget subheads and categories removed.');
    }

    public function downloadTemplate()
    {
        $headers = [
            "MDA CODE", "MDA NAME", "SECTOR", "SUBHEAD CODE", 
            "SUBHEAD DESCRIPTION", "Total Provision 2026", 
            "Expenditure Type", "Additional Provision"
        ];
        
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ["0111001001", "Government House", "Administrative", "22020102", "LOCAL TRAVEL & TRANSPORT", "20000000.00", "RECURRENT", "0.00"]);
            fclose($file);
        };

        return Response::stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=Global_Budget_Template.csv",
        ]);
    }


    public function save()
    {
        $this->validate([
            'budget_file' => 'required|mimes:csv,txt|max:20480', 
        ]);

        try {
            $currentSetting = \App\Models\Setting::where('is_current_year', true)->first();
            $activeYear = $currentSetting ? $currentSetting->fiscal_year : 2026;

            DB::beginTransaction();

            $path = $this->budget_file->getRealPath();
            $file = fopen($path, 'r');
            fgetcsv($file); // Skip Header

            $rowCount = 0;
            $skippedMdaCount = 0;

            while (($row = fgetcsv($file)) !== FALSE) {
                if (empty($row[0]) || empty($row[3])) continue;

                $mdaCodeCSV = trim($row[0]);
                $subheadCodeCSV = trim($row[3]);
                $descriptionCSV = trim($row[4]);
                $approvedProvisionCSV = (float) str_replace(',', '', $row[5]);
                $additionalProvisionCSV = (float) str_replace(',', '', $row[7] ?? 0);

                // 1. Find MDA 
                $mda = Mda::where('mda_code', $mdaCodeCSV)->first();
                
                // Fallback for 10 vs 12 digit codes
                if (!$mda) {
                    $shortCode = substr($mdaCodeCSV, 0, 10);
                    $mda = Mda::where('mda_code', $shortCode)->first();
                }

                if (!$mda) {
                    $skippedMdaCount++;
                    continue; 
                }

                // 2. Map Category
                $rawType = strtoupper(trim($row[6]));
                $type = (strlen($subheadCodeCSV) >= 10) ? 'Capital' : 
                        (str_starts_with($subheadCodeCSV, '1') ? 'Revenue' : 
                        (($rawType === 'RECURRENT') ? (str_starts_with($subheadCodeCSV, '21') ? 'Personnel' : 'Overhead') : 
                        ucfirst(strtolower($rawType))));

                $category = Category::firstOrCreate(['mda_id' => $mda->id, 'type' => $type]);

                // 3. THE "SMART VALIDATION" LOGIC
                // We include 'approved_provision' in the first array. 
                // This ensures that items with the same code/name but different amounts 
                // are treated as separate, valid budget heads (hitting your 2253 goal).
                Subhead::updateOrCreate(
                    [
                        'mda_code'           => $mda->mda_code, 
                        'subhead_code'       => $subheadCodeCSV,
                        'description'        => $descriptionCSV,
                        'fiscal_year'        => $activeYear,
                        'approved_provision' => $approvedProvisionCSV, // Unique identifier
                    ],
                    [
                        'mda_id'               => $mda->id, 
                        'category_id'          => $category->id,
                        'additional_provision' => $additionalProvisionCSV,
                        // We don't reset virement/supplementary to 0 here 
                        // so that subsequent updates don't wipe existing progress.
                    ]
                );

                $rowCount++;
            }
            
            fclose($file);
            DB::commit();

            $resultMsg = "Successfully synchronized $rowCount budget heads.";
            if ($skippedMdaCount > 0) {
                $resultMsg .= " Warning: $skippedMdaCount rows skipped because MDA codes weren't found in your system.";
            }

            $this->dispatch('swal:toast', $resultMsg);
            $this->reset('budget_file');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Upload Error: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.admin.budget-upload', [
            'stats' => [
                'total_subheads' => Subhead::count(),
                'total_mdas' => Mda::has('subheads')->count(),
                'last_entry' => Subhead::latest()->first()?->created_at?->diffForHumans() ?? 'No data'
            ]
        ])->layout('layouts.app');
    }
}