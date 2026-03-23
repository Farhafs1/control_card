<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\Category;
use App\Models\Subhead;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

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
            DB::beginTransaction();

            $path = $this->budget_file->getRealPath();
            $file = fopen($path, 'r');
            fgetcsv($file); // Skip Header

            $rowCount = 0;

            while (($row = fgetcsv($file)) !== FALSE) {
                if (empty($row[0]) || empty($row[3])) continue;

                // Normalize codes (remove spaces/commas)
                $mdaCodeCSV = trim($row[0]);
                $subheadCodeCSV = trim($row[3]);

                // 1. Map to MDA
                $mda = Mda::where('mda_code', $mdaCodeCSV)->first();
                if (!$mda) continue;

                // 2. Map to Category
                $rawType = strtoupper($row[6]);
                $type = ($rawType === 'RECURRENT') 
                    ? (str_starts_with($subheadCodeCSV, '21') ? 'Personnel' : 'Overhead') 
                    : $rawType;

                $category = Category::firstOrCreate([
                    'mda_id' => $mda->id,
                    'type' => ucfirst(strtolower($type)),
                ]);

                // 3. Composite Upsert using the NEW Migration logic (mda_code + subhead_code)
                Subhead::updateOrCreate(
                    [
                        'mda_code' => $mdaCodeCSV, 
                        'subhead_code' => $subheadCodeCSV
                    ],
                    [
                        'mda_id' => $mda->id, // Maintain the relationship link
                        'category_id' => $category->id,
                        'description' => trim($row[4]),
                        'approved_provision' => (float) str_replace(',', '', $row[5]),
                        'additional_provision' => (float) str_replace(',', '', $row[7] ?? 0),
                    ]
                );
                $rowCount++;
            }
            
            fclose($file);
            DB::commit();

            $this->dispatch('swal:toast', "Success: $rowCount subheads synchronized.");
            $this->reset('budget_file');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Critical Upload Error: ' . $e->getMessage());
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