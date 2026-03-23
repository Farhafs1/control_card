<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class MdaManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $name, $mda_code, $user_id, $sector, $mda_secret_code, $is_active = true;
    public $editingMdaId = null;
    public $showForm = false;
    public $search = '';
    public $bulk_file;
    public $selectedMdas = [];
    public $target_officer_id;
    
    // Track import errors to show in the UI
    public $importErrors = [];

    protected $rules = [
        'name' => 'required|string',
        'mda_code' => 'required|string',
        'sector' => 'required|string',
        'user_id' => 'nullable|exists:users,id',
        'mda_secret_code' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    /**
     * Resets the entire MDA table
     */
    public function resetAllMdas()
    {
        // Deletes all records and resets the auto-increment ID
        Mda::query()->delete(); 
        
        $this->importErrors = [];
        session()->flash('message', 'Database reset: All MDA records have been removed.');
    }

    public function downloadTemplate()
    {
        $headers = ["mda_code", "name", "sector", "mda_secret_code"];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ["0111001001", "Ministry of Finance", "Administrative", "FIN-001"]);
            fclose($file);
        };
        return Response::stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=mda_template.csv",
        ]);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'mda_code' => $this->mda_code,
            'sector' => $this->sector,
            'user_id' => $this->user_id,
            'mda_secret_code' => $this->mda_secret_code,
            'is_active' => $this->is_active,
        ];

        if ($this->editingMdaId) {
            Mda::find($this->editingMdaId)->update($data);
            session()->flash('message', 'MDA updated successfully.');
        } else {
            Mda::create($data);
            session()->flash('message', 'New MDA registered successfully.');
        }

        $this->reset(['name', 'mda_code', 'sector', 'user_id', 'mda_secret_code', 'showForm', 'editingMdaId']);
    }

    public function edit($id)
    {
        $mda = Mda::findOrFail($id);
        $this->editingMdaId = $id;
        $this->name = $mda->name;
        $this->mda_code = $mda->mda_code;
        $this->sector = $mda->sector;
        $this->user_id = $mda->user_id;
        $this->mda_secret_code = $mda->mda_secret_code;
        $this->is_active = $mda->is_active;
        $this->showForm = true;
    }

    public function toggleStatus($id)
    {
        $mda = Mda::findOrFail($id);
        $mda->update(['is_active' => !$mda->is_active]);
    }

    public function render()
    {
        return view('livewire.admin.mda-management', [
            'mdas' => Mda::with('user')
                ->where(function($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('mda_code', 'like', '%'.$this->search.'%');
                })
                ->orderBy('mda_code', 'asc') // Government House (01...) will come first
                ->paginate(10),
            'officers' => User::where('role', 'officer')->get()
        ]);
    }
    public function importMdas()
    {
        $this->validate([
            'bulk_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $this->importErrors = [];
        $path = $this->bulk_file->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip header row

        $successCount = 0;
        $rowCount = 1; // Tracking for error reporting

        while (($row = fgetcsv($file)) !== FALSE) {
            $rowCount++;
            
            // Validate basic row data
            if (count($row) < 3 || empty($row[0])) {
                $this->importErrors[] = "Row $rowCount: Incomplete data (missing Code or Name).";
                continue;
            }

            try {
                $mdaCode = $row[0];
                $mdaSecret = !empty($row[3]) ? $row[3] : null;

                // Check for unique constraint on secret code manually to avoid crash
                if ($mdaSecret) {
                    $duplicateSecret = Mda::where('mda_secret_code', $mdaSecret)
                                          ->where('mda_code', '!=', $mdaCode)
                                          ->exists();
                    if ($duplicateSecret) {
                        $this->importErrors[] = "Row $rowCount: Secret code '$mdaSecret' is already assigned to another MDA.";
                        continue;
                    }
                }

                Mda::updateOrCreate(
                    ['mda_code' => $mdaCode],
                    [
                        'name' => $row[1],
                        'sector' => $row[2],
                        'mda_secret_code' => $mdaSecret,
                        'is_active' => true,
                    ]
                );
                $successCount++;
            } catch (\Exception $e) {
                $this->importErrors[] = "Row $rowCount: Database error (check for duplicate Admin Codes).";
            }
        }

        fclose($file);
        $this->reset('bulk_file');

        if (count($this->importErrors) > 0) {
            session()->flash('error', "Import completed with " . count($this->importErrors) . " issues.");
        }
        
        session()->flash('message', "$successCount MDAs processed successfully.");
    }

    public function delete($id)
    {
        Mda::findOrFail($id)->delete();
        $this->dispatch('swal:toast', 'MDA deleted successfully');
    }
}