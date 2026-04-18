<?php

namespace App\Livewire\Officer;

use App\Models\Release;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class RecentReleases extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all';

    /**
     * Reset pagination to page 1 whenever search or filter changes
     */
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function render()
    {
        // 1. Get IDs of MDAs assigned to this officer
        $mdaIds = Auth::user()->mdas()->pluck('mdas.id');

        // 2. Build the query
        $query = Release::whereIn('mda_id', $mdaIds)
            ->with(['mda', 'subhead']);

        // 3. Apply Status Filter
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // 4. Apply Multi-Field Search (Reference, Amount, Date, MDA, Subhead)
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';

            $query->where(function($q) use ($searchTerm) {
                $q->where('reference_no', 'like', $searchTerm)
                  ->orWhere('amount', 'like', $searchTerm)
                  ->orWhere('created_at', 'like', $searchTerm)
                  // Deep search in MDA relationship
                  ->orWhereHas('mda', function($m) use ($searchTerm) {
                      $m->where('name', 'like', $searchTerm)
                        ->orWhere('mda_code', 'like', $searchTerm);
                  })
                  // Deep search in Subhead relationship
                  ->orWhereHas('subhead', function($s) use ($searchTerm) {
                      $s->where('description', 'like', $searchTerm)
                        ->orWhere('subhead_code', 'like', $searchTerm);
                  });
            });
        }

        return view('livewire.officer.recent-releases', [
            'releases' => $query->latest()->paginate(15)
        ])->layout('layouts.app');
    }
}