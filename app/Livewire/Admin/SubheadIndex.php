<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Mda;
use Livewire\WithPagination;

class SubheadIndex extends Component
{
    use WithPagination;

    // This ensures the search property is tracked by Livewire
    public $search = '';

    /**
     * Reset pagination when the search term changes
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Handle the View Details action
     */
    public function selectMda($id)
    {
        // Redirect to the specific MDA's subhead details page
        return redirect()->route('admin.subheads.show', ['mda' => $id]);
    }

    public function render()
    {
        $mdas = Mda::query()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $searchTerm = '%' . $this->search . '%';
                    
                    // Using 'ilike' for PostgreSQL case-insensitive search.
                    // This handles both Name and MDA Code equally.
                    $q->where('name', 'ilike', $searchTerm)
                      ->orWhere('mda_code', 'ilike', $searchTerm);
                });
            })
            ->withSum('subheads as approved_total', 'approved_provision')
            ->withSum('subheads as additional_total', 'additional_provision')
            ->orderBy('mda_code', 'asc')
            ->paginate(15);

        return view('livewire.admin.subheads.subhead-index', [
            'mdas' => $mdas
        ])->layout('layouts.app');
    }
}