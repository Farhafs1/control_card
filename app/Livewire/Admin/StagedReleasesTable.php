<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ScrapedRelease;
use App\Models\Release;
use App\Models\Mda;
use App\Models\Subhead;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class StagedReleasesTable extends Component
{
    use WithPagination;

    public $showEditModal = false;
    public $perPage = 20; 
    
    // Search and Filter properties
    public $search = '';
    public $filter = 'all'; // Options: all, invalid_mda, duplicates, name_mismatch
    public $statusFilter = 'all'; // NEW: status filtering (approved, circulating, returned)

    // Properties for editing
    public $editingId;
    public $mda_code;
    public $mda_name; // NEW: MDA Name for editing
    public $subhead_code;
    public $narration;
    public $amount;
    // 1. Add the property at the top with the others
    public $status; 


    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
    ];

    // Reset pagination when searching or filtering
    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    /**
     * Logic to approve and move to the main ledger
     * Streamlined to use MDA Name if Code is missing.
     */
    public function approve($id)
    {
        // 1. Retrieve the staged record
        $staged = ScrapedRelease::findOrFail($id);
            
        // 2. PROTECTION GUARD: Only allow records with 'approved' status to proceed
        if ($staged->status !== 'approved') {
            $this->sendAlert(
                'Action Blocked', 
                'This record is currently "' . ucfirst($staged->status) . '". You can only approve records marked as "Approved" on E-Budget.', 
                'error'
            );
            return;
        }

        // 3. MDA Mapping (Code first, then fallback to edited Name)
        $mdaCode = trim($staged->mda_code);
        $mda = Mda::where('mda_code', $mdaCode)->first();
        
        if (!$mda && !empty($staged->mda_name)) {
            $mda = Mda::where('name', 'like', '%' . trim($staged->mda_name) . '%')->first();
        }

        if (!$mda) {
            $this->sendAlert('MDA Mapping Error', "Record [{$staged->mda_name}] does not match any local MDA.", 'error');
            return;
        }

        // 4. Subhead Mapping
        $subheadCode = trim($staged->subhead_code);
        if (empty($subheadCode)) {
            $this->sendAlert('Missing Subhead', "Record has no subhead code.", 'error');
            return;
        }

        $subhead = Subhead::where('subhead_code', $subheadCode)
                            ->where('mda_id', $mda->id)
                            ->first();
        
        if (!$subhead) {
            $this->sendAlert('Subhead Error', "Subhead [{$subheadCode}] is not linked to {$mda->name}.", 'error');
            return;
        }

        // 5. Duplicate Check against Permanent Ledger
        $isDuplicate = Release::where('mda_code', $mda->mda_code)
            ->where('subhead_code', $subheadCode)
            ->where('reference_no', trim($staged->reference_no))
            ->where('amount', $staged->amount)
            ->whereDate('release_date', $staged->release_date)
            ->exists();

        // 6. Transactional Transfer
        try {
            DB::transaction(function () use ($staged, $mda, $subhead) {
                Release::create([
                    'user_id'      => auth()->id() ?? 1,
                    'mda_id'       => $mda->id,
                    'subhead_id'   => $subhead->id,
                    'mda_code'     => $mda->mda_code,
                    // We use $staged->mda_name here to capture your manual edits/corrections
                    'mda_name'     => $staged->mda_name ?: $mda->name, 
                    'subhead_code' => $subhead->subhead_code,
                    'release_date' => $staged->release_date,
                    'reference_no' => $staged->reference_no,
                    'amount'       => $staged->amount,
                    'narration'    => $staged->narration,
                ]);

                // POP OUT: Remove from staging table now that it is safely in the ledger
                $staged->delete();
            });

            $statusType = $isDuplicate ? 'warning' : 'success';
            $message = $isDuplicate ? "Duplicate registered anyway." : "Record moved to ledger.";
            $this->sendAlert('Approved', $message, $statusType);

        } catch (\Exception $e) {
            $this->sendAlert('Error', 'Transaction Failed: ' . $e->getMessage(), 'error');
        }
    }
    /**
     * Helper for UI badges (MDA/Subhead/Duplicate/Name Mismatch)
     */
    public function getValidationStatus($release)
    {
        $mda = Mda::where('mda_code', trim($release->mda_code))->first();
        $mdaExists = (bool)$mda;
        
        $nameMismatch = false;
        if ($mdaExists && !empty($release->mda_name)) {
            if (strtolower(trim($mda->name)) !== strtolower(trim($release->mda_name))) {
                $nameMismatch = true;
            }
        }

        $subheadExists = false;
        if ($mdaExists && !empty($release->subhead_code)) {
            $subheadExists = Subhead::where('subhead_code', trim($release->subhead_code))
                ->where('mda_id', $mda->id)
                ->exists();
        }

        $isDuplicate = Release::where('mda_code', trim($release->mda_code))
                ->where('subhead_code', trim($release->subhead_code))
                ->where('reference_no', trim($release->reference_no))
                ->where('amount', $release->amount)
                ->whereDate('release_date', $release->release_date)
                ->exists();
        // Old duplicate checker
        // $isDuplicate = Release::where('reference_no', trim($release->reference_no))
        //         ->where('amount', $release->amount)
        //         ->exists();

        return [
            'mda_exists' => $mdaExists,
            'name_mismatch' => $nameMismatch,
            'subhead_exists' => $subheadExists,
            'is_duplicate' => $isDuplicate,
        ];
    }

    // public function edit($id)
    // {
    //     $record = ScrapedRelease::findOrFail($id);
    //     $this->editingId = $id;
    //     $this->mda_code = $record->mda_code;
    //     $this->mda_name = $record->mda_name;
    //     $this->subhead_code = $record->subhead_code;
    //     $this->narration = $record->narration;
    //     $this->amount = $record->amount;
    //     $this->showEditModal = true;
    // }

    // public function update()
    // {
    //     $record = ScrapedRelease::findOrFail($this->editingId);

    //     // Check duplicates in staging
    //     // $exists = ScrapedRelease::where('reference_no', $record->reference_no)
    //     //     ->where('mda_code', $this->mda_code)
    //     //     ->where('subhead_code', $this->subhead_code)
    //     //     ->where('id', '!=', $this->editingId)
    //     //     ->exists();

    //     // if ($exists) {
    //     //     $this->dispatch('swal:modal', [
    //     //         'type'    => 'warning',
    //     //         'title'   => 'Duplicate Detected',
    //     //         'text'    => "This combination already exists in staging.",
    //     //     ]);
    //     //     return;
    //     // }

    //     $record->update([
    //         'mda_code' => $this->mda_code,
    //         'mda_name' => $this->mda_name,
    //         'subhead_code' => $this->subhead_code,
    //         'narration' => $this->narration,
    //         'amount' => $this->amount,
    //     ]);

    //     $this->showEditModal = false;
    //     $this->sendAlert('Updated', 'Record modified.', 'success');
    // }

    
    // 2. Update the edit() method to load the current status
    public function edit($id)
    {
        $record = ScrapedRelease::findOrFail($id);
        $this->editingId = $id;
        $this->mda_code = $record->mda_code;
        $this->mda_name = $record->mda_name;
        $this->subhead_code = $record->subhead_code;
        $this->narration = $record->narration;
        $this->amount = $record->amount;
        $this->status = $record->status; // <--- Add this
        $this->showEditModal = true;
    }

    // 3. Update the update() method to save the manual status change
    public function update()
    {
        $record = ScrapedRelease::findOrFail($this->editingId);

        $record->update([
            'mda_code' => $this->mda_code,
            'mda_name' => $this->mda_name,
            'subhead_code' => $this->subhead_code,
            'narration' => $this->narration,
            'amount' => $this->amount,
            'status' => $this->status, // <--- Add this
        ]);

        $this->showEditModal = false;
        $this->sendAlert('Updated', 'Record and Status modified.', 'success');
    }

    public function confirmDiscard($id) { $this->dispatch('confirm-discard', id: $id); }

    public function discard($id)
    {
        ScrapedRelease::destroy($id);
        $this->sendAlert('Discarded', 'Record removed.', 'info');
    }

    public function truncateQueue()
    {
        ScrapedRelease::truncate();
        $this->sendAlert('Queue Cleared', 'All records deleted.', 'success');
    }

    private function sendAlert($title, $text, $icon)
    {
        $this->dispatch('swal', [
            'title' => $title, 'text' => $text, 'icon' => $icon,
            'timer' => 3000, 'showConfirmButton' => false,
            'position' => 'top-end', 'toast' => true
        ]);
    }

    public function render()
    {
        $query = ScrapedRelease::query();

        // 1. Search Logic (Enhanced with mda_name)
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('reference_no', 'like', '%' . $this->search . '%')
                ->orWhere('narration', 'like', '%' . $this->search . '%')
                ->orWhere('mda_name', 'like', '%' . $this->search . '%')
                ->orWhere('mda_code', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Status Filtering (Approved, Circulating, Returned)
        // This allows the user to toggle between categories
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // 3. Data Quality Filtering
        if ($this->filter === 'invalid_mda') {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('mdas')
                ->whereRaw('mdas.mda_code = scraped_releases.mda_code');
            });
        } elseif ($this->filter === 'name_mismatch') {
            // CRITICAL: select only staged columns to avoid ID conflicts with the joined table
            $query->select('scraped_releases.*') 
                ->join('mdas', 'scraped_releases.mda_code', '=', 'mdas.mda_code')
                ->whereRaw('LOWER(scraped_releases.mda_name) != LOWER(mdas.name)');
        } elseif ($this->filter === 'duplicates') {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('releases')
                ->whereRaw('releases.mda_code = scraped_releases.mda_code')
                ->whereRaw('releases.subhead_code = scraped_releases.subhead_code')
                ->whereRaw('releases.reference_no = scraped_releases.reference_no')
                ->whereRaw('releases.amount = scraped_releases.amount')
                ->whereRaw('DATE(releases.release_date) = DATE(scraped_releases.release_date)');
            });
        } 
              
        // elseif ($this->filter === 'duplicates') {
        //     $query->whereExists(function ($q) {
        //         $q->select(DB::raw(1))->from('releases')
        //         ->whereRaw('releases.reference_no = scraped_releases.reference_no')
        //         ->whereRaw('releases.amount = scraped_releases.amount');
        //     });
        // }

        // 4. Final Output with consistent ordering
        return view('livewire.admin.staged-releases-table', [
            'releases' => $query->orderBy('release_date', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->paginate($this->perPage)
        ]);
    }
}