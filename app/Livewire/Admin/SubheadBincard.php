<?php

namespace App\Livewire\Admin;

use App\Models\Subhead;
use App\Models\Release;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class SubheadBinCard extends Component
{
    use WithPagination;

    public Subhead $subhead;
    
    // Inline Entry Properties
    public $editingReleaseId = null;
    public $editForm = [
        'release_date' => '',
        'reference_no' => '',
        'amount' => '',
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
        
        // Ensure the MDA relationship is available
        if (!$this->subhead->relationLoaded('mda')) {
            $this->subhead->load('mda');
        }

        $categoryType = strtolower($this->subhead->category->type ?? '');
        $subheadDesc = strtolower($this->subhead->description ?? '');
        
        // 1. Determine Category Part
        $typePart = "";
        if (str_contains($categoryType, 'cap')) {
            $typePart = "CAP/";
        } elseif (str_contains($categoryType, 'pers')) {
            $typePart = "REC/SALARY/";
        } elseif (str_contains($categoryType, 'over')) {
            // Distinguish between standard Overhead and General Recurrent
            if (str_contains($subheadDesc, 'overhead') && !str_contains($subheadDesc, 'general')) {
                $typePart = "OVH/";
            } else {
                $typePart = "REC/";
            }
        }

        // 2. Secret Code Part (Using the correct mda_secret_code column)
        $mdaPart = "";
        if (str_contains($categoryType, 'cap') || str_contains($categoryType, 'over')) {
            // Accessing the specific column name you provided
            $secretNo = $this->subhead->mda->mda_secret_code ?? null;
            
            if ($secretNo !== null) {
                $mdaPart = "S." . $secretNo . "/";
            } else {
                $mdaPart = "S.XX/"; // Fallback if database value is empty
            }
        }

        // 3. File Volume Part
        $volPart = "VOL.I/";

        $this->newRelease['reference_no'] = $constant . $typePart . $mdaPart . $volPart;
    }

    public function editRelease($id)
    {
        $release = Release::findOrFail($id);
        $this->editingReleaseId = $id;
        $this->editForm = [
            'release_date' => $release->release_date->format('Y-m-d'),
            'reference_no' => $release->reference_no,
            'amount'       => $release->amount,
        ];
    }

    public function cancelEdit()
    {
        $this->editingReleaseId = null;
        $this->resetErrorBag();
    }

    public function updateRelease()
    {
        $this->validate([
            'editForm.release_date' => 'required|date',
            'editForm.reference_no' => 'required|unique:releases,reference_no,' . $this->editingReleaseId,
            'editForm.amount'       => 'required|numeric|min:0',
        ]);

        $release = Release::findOrFail($this->editingReleaseId);
        $release->update($this->editForm);

        $this->editingReleaseId = null;
        session()->flash('success', 'Release updated successfully.');
    }

    public function saveNewRelease()
    {
        $this->validate([
            'newRelease.release_date' => 'required|date',
            'newRelease.reference_no' => 'required|unique:releases,reference_no',
            'newRelease.amount'       => 'required|numeric|min:0',
        ]);

        Release::create([
            'subhead_id'   => $this->subhead->id,
            'mda_id'       => $this->subhead->mda_id,
            'release_date' => $this->newRelease['release_date'],
            'reference_no' => $this->newRelease['reference_no'],
            'amount'       => $this->newRelease['amount'],
            'narration'    => $this->newRelease['narration'],
        ]);

        // Reset and Regenerate the Prefix for the next entry
        $this->newRelease = [
            'release_date' => now()->format('Y-m-d'),
            'reference_no' => '',
            'amount'       => '',
            'narration'    => 'Release recorded via Bin Card',
        ];
        
        $this->generateReferencePrefix();

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
        $releases = Release::where('subhead_id', $this->subhead->id)
            ->orderBy('release_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20);

        // Calculate global expenditure for header cards
        $totalExp = Release::where('subhead_id', $this->subhead->id)
            ->where('is_cancelled', false)
            ->sum('amount');
        
        $balance = $this->subhead->total_budget - $totalExp;
        $percentLeft = ($this->subhead->total_budget > 0) ? ($balance / $this->subhead->total_budget) * 100 : 0;

        // Balance Percentage Logic for UI Indicators
        $statusColor = match(true) {
            $balance < 0 => 'text-rose-600',
            $percentLeft <= 5 => 'text-yellow-500',
            $percentLeft <= 25 => 'text-orange-500',
            default => 'text-emerald-500',
        };

        $categoryName = Str::lower($this->subhead->category->name ?? 'default');

        $theme = match(true) {
            str_contains($categoryName, 'cap') => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'accent' => 'text-emerald-700', 'button' => 'bg-emerald-600', 'ring' => 'focus:ring-emerald-500'],
            str_contains($categoryName, 'over') => ['bg' => 'bg-amber-50', 'border' => 'border-amber-100', 'accent' => 'text-amber-700', 'button' => 'bg-amber-600', 'ring' => 'focus:ring-amber-500'],
            str_contains($categoryName, 'pers') => ['bg' => 'bg-orange-50', 'border' => 'border-orange-100', 'accent' => 'text-orange-700', 'button' => 'bg-orange-600', 'ring' => 'focus:ring-orange-500'],
            str_contains($categoryName, 'rev') => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'accent' => 'text-blue-700', 'button' => 'bg-blue-600', 'ring' => 'focus:ring-blue-500'],
            default => ['bg' => 'bg-slate-50', 'border' => 'border-slate-100', 'accent' => 'text-slate-700', 'button' => 'bg-slate-600', 'ring' => 'focus:ring-slate-500'],
        };

        // Initialize variables for chronological running totals
        $runningTotalReleased = 0;
        $runningBalance = $this->subhead->total_budget;

        // Correctly calculate the starting balance for paginated pages
        if (!$releases->onFirstPage() && $releases->count() > 0) {
            $firstOnPage = $releases->first();
            
            $previousSum = Release::where('subhead_id', $this->subhead->id)
                ->where('is_cancelled', false)
                ->where(function($query) use ($firstOnPage) {
                    $query->where('release_date', '<', $firstOnPage->release_date)
                          ->orWhere(function($sub) use ($firstOnPage) {
                              $sub->where('release_date', $firstOnPage->release_date)
                                  ->where('id', '<', $firstOnPage->id);
                          });
                })->sum('amount');
            
            $runningTotalReleased = $previousSum;
            $runningBalance -= $previousSum;
        }

        return view('livewire.admin.subhead-bin-card', [
            'releases'             => $releases,
            'initialTotalReleased' => $runningTotalReleased,
            'initialBalance'       => $runningBalance,
            'totalExpenditure'     => $totalExp,
            'balance'              => $balance,
            'percentLeft'          => $percentLeft,
            'statusColor'          => $statusColor,
            'themeClasses'         => $theme 
        ])->layout('layouts.app');
    }
}