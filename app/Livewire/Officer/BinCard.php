<?php

namespace App\Livewire\Officer;

use App\Models\Subhead;
use App\Models\Release;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BinCard extends Component
{
    public Subhead $subhead;
    
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
        'narration' => '', 
    ];

    public function mount(Subhead $subhead)
    {
        if (!Auth::user()->mdas()->where('id', $subhead->mda_id)->exists()) {
            abort(403, 'Unauthorized access to this Control Card.');
        }

        $this->subhead = $subhead->load(['mda', 'category']);
        $this->newRelease['release_date'] = now()->format('Y-m-d');
        $this->generateReferencePrefix();
    }

    public function generateReferencePrefix()
    {
        $constant = "KTS/MBEP/BD/";
        $subheadCode = (string)$this->subhead->subhead_code;
        $typePart = "";
        
        if (strlen($subheadCode) >= 10 || str_starts_with($subheadCode, '4')) {
            $typePart = "CAP/";
        } elseif (str_starts_with($subheadCode, '210')) {
            $typePart = "REC/SALARY/";
        } elseif (str_starts_with($subheadCode, '220')) {
            $typePart = "REC/";
        } elseif (str_starts_with($subheadCode, '1')) {
            $typePart = "REV/";
        }

        $secretNo = $this->subhead->mda->mda_secret_code ?? 'XX';
        $mdaPart = "S." . $secretNo . "/";

        $this->newRelease['reference_no'] = $constant . $typePart . $mdaPart . "VOL.I/";
    }

    public function editRelease($id)
    {
        try {
            // Context security verification to protect MDA boundaries during edits
            $release = Release::whereHas('mda', function($q) {
                $q->whereIn('id', Auth::user()->mdas->pluck('id'));
            })->findOrFail($id);

            $this->editingReleaseId = $id;
            
            // Explicit standard system format presentation serialization
            $this->editForm = [
                'release_date' => Carbon::parse($release->release_date)->format('Y-m-d'),
                'reference_no' => $release->reference_no,
                'amount'       => $release->amount,
                'narration'    => $release->narration ?? '',
            ];
            
            session()->flash('success', 'Now editing record');
            
        } catch (\Exception $e) {
            \Log::error('Edit release failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Could not edit release.');
        }
    }

    public function updateRelease()
    {
        $release = Release::where('id', $this->editingReleaseId)
            ->where(function($query) {
                $query->where('subhead_id', $this->subhead->id)
                      ->orWhere('subhead_code', $this->subhead->subhead_code);
            })
            ->first();
        
        if (!$release) {
            session()->flash('error', 'Release not found or unauthorized.');
            $this->cancelEdit();
            return;
        }
        
        $this->validate([
            'editForm.release_date' => 'required|date',
            'editForm.reference_no' => 'required|unique:releases,reference_no,' . $this->editingReleaseId . ',id',
            'editForm.amount'       => 'required|numeric|min:0',
            'editForm.narration'    => 'nullable|string',
        ]);
        
        // Explicitly force system parse conversion to strip tracking mutations
        $parsedDate = Carbon::createFromFormat('Y-m-d', $this->editForm['release_date'])->startOfDay();

        $release->update([
            'release_date' => $parsedDate,
            'reference_no' => $this->editForm['reference_no'],
            'amount'       => $this->editForm['amount'],
            'narration'    => $this->editForm['narration'] ?? '',
        ]);
        
        $this->cancelEdit();
        session()->flash('success', 'Release updated successfully.');
    }

    public function cancelEdit()
    {
        $this->editingReleaseId = null;
        $this->reset('editForm');
    }

    public function saveNewRelease()
    {
        $this->validate([
            'newRelease.release_date' => 'required|date',
            'newRelease.reference_no' => 'required|unique:releases,reference_no',
            'newRelease.amount'       => 'required|numeric|min:0',
            'newRelease.narration'    => 'nullable|string',
        ]);

        Release::create([
            'subhead_id'   => $this->subhead->id,
            'subhead_code' => $this->subhead->subhead_code,
            'mda_id'       => $this->subhead->mda_id,
            'mda_code'     => $this->subhead->mda->mda_code ?? $this->subhead->mda_code,
            'release_date' => $this->newRelease['release_date'],
            'reference_no' => $this->newRelease['reference_no'],
            'amount'       => $this->newRelease['amount'],
            'narration'    => $this->newRelease['narration'] ?? '',
        ]);

        $this->reset('newRelease');
        $this->newRelease['release_date'] = now()->format('Y-m-d');
        $this->generateReferencePrefix();

        session()->flash('success', 'New release record posted to ledger.');
    }

    public function deleteRelease($id)
    {
        $release = Release::whereHas('mda', function($q) {
            $q->whereIn('id', Auth::user()->mdas->pluck('id'));
        })->findOrFail($id);

        $release->delete();
        session()->flash('success', 'Release deleted.');
    }

    public function render()
    {
        $releases = Release::where('subhead_code', $this->subhead->subhead_code)
            ->where('mda_code', $this->subhead->mda->mda_code ?? $this->subhead->mda_code)
            ->orderBy('release_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculations
        $totalBudget = $this->subhead->total_budget ?? 0;
        $totalExp = $releases->where('is_cancelled', '!=', true)->sum('amount');
        $balance = $totalBudget - $totalExp;
        
        // Percentages
        $percentSpent = ($totalBudget > 0) ? ($totalExp / $totalBudget) * 100 : 0;
        $percentLeft = ($totalBudget > 0) ? ($balance / $totalBudget) * 100 : 0;

        $statusColor = match(true) {
            $balance < 0 => 'text-rose-600',
            $percentLeft <= 5 => 'text-yellow-500',
            $percentLeft <= 25 => 'text-orange-500',
            default => 'text-emerald-500',
        };

        $subheadCode = (string)$this->subhead->subhead_code;
        $categoryName = Str::lower($this->subhead->category->name ?? 'default');

        $theme = match(true) {
            $this->subhead->is_personnel ?? str_starts_with($subheadCode, '210') || str_contains($categoryName, 'pers') => ['bg' => 'bg-orange-50', 'border' => 'border-orange-100', 'accent' => 'text-orange-700', 'button' => 'bg-orange-600', 'ring' => 'focus:ring-orange-500'],
            $this->subhead->is_capital ?? strlen($subheadCode) >= 10 || str_contains($categoryName, 'cap') => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'accent' => 'text-emerald-700', 'button' => 'bg-emerald-600', 'ring' => 'focus:ring-emerald-500'],
            $this->subhead->is_overhead ?? str_starts_with($subheadCode, '220') || str_contains($categoryName, 'over') => ['bg' => 'bg-amber-50', 'border' => 'border-amber-100', 'accent' => 'text-amber-700', 'button' => 'bg-amber-600', 'ring' => 'focus:ring-amber-500'],
            str_starts_with($subheadCode, '1') || str_contains($categoryName, 'rev') => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'accent' => 'text-blue-700', 'button' => 'bg-blue-600', 'ring' => 'focus:ring-blue-500'],
            default => ['bg' => 'bg-slate-50', 'border' => 'border-slate-100', 'accent' => 'text-slate-700', 'button' => 'bg-slate-600', 'ring' => 'focus:ring-slate-500'],
        };

        return view('livewire.officer.subheads.bin-card', [
            'releases'           => $releases,
            'totalSpent'         => $totalExp, // Renamed for consistency
            'percentSpent'       => $percentSpent, // New variable
            'balance'            => $balance,
            'percentLeft'        => $percentLeft,
            'statusColor'        => $statusColor,
            'themeClasses'       => $theme 
        ])->layout('layouts.app');
    }
}