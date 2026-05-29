<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\Category;
use App\Models\Subhead;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

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

            Subhead::with(['mda', 'category'])->chunk(500, function($subheads) use ($file) {
                foreach ($subheads as $subhead) {
                    fputcsv($file, [
                        $subhead->mda_code, 
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
                
                // Correct Column Indexes based on your exact CSV headers:
                $approvedProvisionCSV   = (float) str_replace(',', '', $row[5]); // Index 5 is APPROVED_PROVISION
                $additionalProvisionCSV = (float) str_replace(',', '', $row[6] ?? 0); // Index 6 is ADDITIONAL_PROVISION

                // 1. Find MDA 
                $mda = Mda::where('mda_code', $mdaCodeCSV)->first();
                
                if (!$mda) {
                    $shortCode = substr($mdaCodeCSV, 0, 10);
                    $mda = Mda::where('mda_code', $shortCode)->first();
                }

                if (!$mda) {
                    $skippedMdaCount++;
                    continue; 
                }

                // 2. EXPLICIT SUBHEAD CODE MAPPING LOGIC 
                $subheadCodeCSV = preg_replace('/[^0-9]/', '', $subheadCodeCSV); // Ensure numeric-only code processing
                $codeLength = strlen($subheadCodeCSV);
                $type = 'Overhead Cost'; // Safe default fallback

                if ($codeLength > 8) {
                    $type = 'Capital';
                } elseif ($codeLength === 8) {
                    if (str_starts_with($subheadCodeCSV, '21')) {
                        $type = 'Personnel';
                    } elseif (str_starts_with($subheadCodeCSV, '22')) {
                        $type = 'Overhead';
                    } elseif (str_starts_with($subheadCodeCSV, '1')) {
                        $type = 'Revenue';
                    }
                }

                // Fetch or save the category structure bound to this MDA
                $category = Category::firstOrCreate([
                    'mda_id' => $mda->id, 
                    'type'   => $type
                ]);

                // 3. Update or Store Budget Lines safely
                Subhead::updateOrCreate(
                    [
                        'mda_code'           => $mda->mda_code, 
                        'subhead_code'       => $subheadCodeCSV,
                        'description'        => $descriptionCSV,
                        'fiscal_year'        => $activeYear,
                        'approved_provision' => $approvedProvisionCSV, 
                    ],
                    [
                        'mda_id'               => $mda->id, 
                        'category_id'          => $category->id,
                        'additional_provision' => $additionalProvisionCSV, // Now maps cleanly to 0.00
                    ]
                );

                $rowCount++;
            }
            
            fclose($file);
            DB::commit();

            $resultMsg = "Successfully synchronized $rowCount budget heads with correct allocation mappings.";
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