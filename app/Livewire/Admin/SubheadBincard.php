<?php

namespace App\Livewire\Admin;

use App\Models\Subhead;
use App\Models\Release;
use Livewire\Component;
use Illuminate\Support\Str;

class SubheadBinCard extends Component
{
    // Pagination removed as requested for the full-ledger view
    public Subhead $subhead;
    
    // Inline Entry Properties
    public $editingReleaseId = null;
    public $editForm = [
        'release_date' => '',
        'reference_no' => '',
        'amount' => '',
        'narration' => '',
    ];

    public $newRelease = [
        'release_date' => '',
        'reference_no' => '',
        'amount' => '',
        'narration' => 'Release recorded via Bin Card',
    ];

    public function mount(Subhead $subhead)
    {
        $this->subhead = $subhead->load(['mda', 'category']);
        $this->newRelease['release_date'] = now()->format('Y-m-d');
        $this->generateReferencePrefix();
    }

    /**
     * Crafts the reference number based on government standards
     */
    public function generateReferencePrefix()
    {
        $constant = "KTS/MBEP/BD/";
        $subheadCode = (string)$this->subhead->subhead_code;
        $categoryType = strtolower($this->subhead->category->type ?? '');
        
        // 1. Determine Category Part based on NCOA prefixes
        $typePart = "";
        
        // Capital: 10 digits or starts with 4
        if (strlen($subheadCode) >= 10 || str_starts_with($subheadCode, '4')) {
            $typePart = "CAP/";
        } 
        // Personnel / Salary: Starts with 210
        elseif (str_starts_with($subheadCode, '210')) {
            $typePart = "REC/SALARY/";
        } 
        // Recurrent Overhead: Starts with 220
        elseif (str_starts_with($subheadCode, '220')) {
            $typePart = "REC/";
        }
        // Revenue: Starts with 110 or 120
        elseif (str_starts_with($subheadCode, '110') || str_starts_with($subheadCode, '120')) {
            $typePart = "REV/";
        }

        // 2. Secret Code Part
        // We now apply the secret code to all types for consistency
        $secretNo = $this->subhead->mda->mda_secret_code ?? 'XX';
        $mdaPart = "S." . $secretNo . "/";
        
        // 3. File Volume Part
        $volPart = "VOL.I/";

        // Final Assembly
        $this->newRelease['reference_no'] = $constant . $typePart . $mdaPart . $volPart;
    }

    public function editRelease($id)
    {
        $release = Release::findOrFail($id);
        $this->editingReleaseId = $id;
        
        $this->editForm = [
            // Use Carbon::parse to safely handle strings from the database
            'release_date' => \Carbon\Carbon::parse($release->release_date)->format('Y-m-d'),
            'reference_no' => $release->reference_no,
            'amount'       => $release->amount,
            'narration'    => $release->narration,
        ];
    }

    public function updateRelease()
    {
        $this->validate([
            'editForm.release_date' => 'required|date',
            'editForm.reference_no' => 'required|unique:releases,reference_no,' . $this->editingReleaseId,
            'editForm.amount'       => 'required|numeric|min:0',
            'editForm.narration'    => 'nullable|string',
        ]);

        $release = Release::findOrFail($this->editingReleaseId);
        
        // Ensure the codes stay synced with the subhead during update
        $release->update([
            'release_date' => $this->editForm['release_date'],
            'reference_no' => $this->editForm['reference_no'],
            'amount'       => $this->editForm['amount'],
            'narration'    => $this->editForm['narration'],
            'mda_code'     => $this->subhead->mda_code,
            'subhead_code' => $this->subhead->subhead_code,
            'mda_id'       => $this->subhead->mda_id,
            'subhead_id'   => $this->subhead->id,
        ]);

        $this->editingReleaseId = null;
        session()->flash('success', 'Release updated successfully.');
    }

    public function cancelEdit()
    {
        $this->editingReleaseId = null;
        $this->resetErrorBag();
    }

    // public function updateRelease()
    // {
    //     $this->validate([
    //         'editForm.release_date' => 'required|date',
    //         'editForm.reference_no' => 'required|unique:releases,reference_no,' . $this->editingReleaseId,
    //         'editForm.amount'       => 'required|numeric|min:0',
    //         'editForm.narration'    => 'nullable|string',
    //     ]);

    //     $release = Release::findOrFail($this->editingReleaseId);
    //     $release->update($this->editForm);

    //     $this->editingReleaseId = null;
    //     session()->flash('success', 'Release updated successfully.');
    // }

    public function saveNewRelease()
    {
        $this->validate([
            'newRelease.release_date' => 'required|date',
            'newRelease.reference_no' => 'required|unique:releases,reference_no',
            'newRelease.amount'       => 'required|numeric|min:0',
        ]);

        Release::create([
            'subhead_id'   => $this->subhead->id,
            'subhead_code' => $this->subhead->subhead_code, // Add this line to fix the error
            'mda_id'       => $this->subhead->mda_id,
            'mda_code'     => $this->subhead->mda_code,
            'release_date' => $this->newRelease['release_date'],
            'reference_no' => $this->newRelease['reference_no'],
            'amount'       => $this->newRelease['amount'],
            'narration'    => $this->newRelease['narration'] ?? 'Release recorded via Bin Card',
        ]);

        // Reset and Regenerate for the next entry
        $this->newRelease = [
            'release_date' => now()->format('Y-m-d'),
            'reference_no' => '',
            'amount'       => '',
            'narration'    => 'Release recorded via Bin Card',
        ];
        
        $this->generateReferencePrefix();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Release recorded successfully!'
        ]);

        session()->flash('success', 'New release added to ledger.');
    }

    public function deleteRelease($id)
    {
        $release = Release::findOrFail($id);
        $release->delete();
        session()->flash('success', 'Release deleted.');
    }

    public function render()
    {
        // 1. CRITICAL FIX: Query by Code instead of ID
        // This ensures releases find the subhead even if the ID changed during re-upload
        $releases = Release::where('subhead_code', $this->subhead->subhead_code)
            ->where('mda_code', $this->subhead->mda_code) 
            ->orderBy('release_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 2. Budget Calculation
        // Ensure 'total_budget' exists on your model, or use (approved_provision + additional_provision)
        $totalExp = $releases->where('is_cancelled', false)->sum('amount');
        $balance = $this->subhead->total_budget - $totalExp;
        $percentLeft = ($this->subhead->total_budget > 0) ? ($balance / $this->subhead->total_budget) * 100 : 0;

        $statusColor = match(true) {
            $balance < 0 => 'text-rose-600',
            $percentLeft <= 5 => 'text-yellow-500',
            $percentLeft <= 25 => 'text-orange-500',
            default => 'text-emerald-500',
        };

        // 3. Robust Theme Logic (Using the NCOA Prefixes we discussed)
        $categoryName = Str::lower($this->subhead->category->name ?? 'default');
        $subheadCode = (string)$this->subhead->subhead_code;

        $theme = match(true) {
            // Salary/Personnel (Prefix 210)
            str_starts_with($subheadCode, '210') || str_contains($categoryName, 'pers') 
                => ['bg' => 'bg-orange-50', 'border' => 'border-orange-100', 'accent' => 'text-orange-700', 'button' => 'bg-orange-600', 'ring' => 'focus:ring-orange-500'],
            
            // Capital (10 digits or prefix 4)
            strlen($subheadCode) >= 10 || str_contains($categoryName, 'cap') 
                => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'accent' => 'text-emerald-700', 'button' => 'bg-emerald-600', 'ring' => 'focus:ring-emerald-500'],
            
            // Overhead (Prefix 220)
            str_starts_with($subheadCode, '220') || str_contains($categoryName, 'over') 
                => ['bg' => 'bg-amber-50', 'border' => 'border-amber-100', 'accent' => 'text-amber-700', 'button' => 'bg-amber-600', 'ring' => 'focus:ring-amber-500'],
            
            // Revenue (Prefix 110/120)
            str_starts_with($subheadCode, '1') || str_contains($categoryName, 'rev') 
                => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'accent' => 'text-blue-700', 'button' => 'bg-blue-600', 'ring' => 'focus:ring-blue-500'],
            
            default => ['bg' => 'bg-slate-50', 'border' => 'border-slate-100', 'accent' => 'text-slate-700', 'button' => 'bg-slate-600', 'ring' => 'focus:ring-slate-500'],
        };

        return view('livewire.admin.subhead-bin-card', [
            'releases'             => $releases,
            'initialTotalReleased' => 0,
            'initialBalance'       => $this->subhead->total_budget,
            'totalExpenditure'     => $totalExp,
            'balance'              => $balance,
            'percentLeft'          => $percentLeft,
            'statusColor'          => $statusColor,
            'themeClasses'         => $theme 
        ])->layout('layouts.app');
    }
}