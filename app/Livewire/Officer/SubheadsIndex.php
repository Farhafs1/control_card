<?php

namespace App\Livewire\Officer;

use App\Models\Mda;
use App\Models\Subhead;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SubheadsIndex extends Component
{
    public $selectedMdaId = null;
    public $activeCategory = 'Personnel';
    public $search = '';

    public function selectMda($id)
    {
        if (Auth::user()->mdas()->where('id', $id)->exists()) {
            $this->selectedMdaId = $id;
        }
    }

    public function resetSelection()
    {
        $this->selectedMdaId = null;
    }

    public function setCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        if ($this->selectedMdaId) {
            return $this->renderDetailView();
        }

        return $this->renderListView();
    }

    private function renderListView()
    {
        $mdas = Auth::user()->mdas()
            ->where(function($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('mda_code', 'like', '%'.$this->search.'%');
            })
            ->withSum('subheads as total_provision', DB::raw('approved_provision + additional_provision'))
            ->paginate(10);

        return view('livewire.officer.subheads.subhead-index', [
            'mdas' => $mdas
        ]);
    }

    private function renderDetailView()
    {
        // 1. Get the MDA and ensure ownership
        $mda = Auth::user()->mdas()->findOrFail($this->selectedMdaId);
        
        // 2. Fetch ALL subheads for this MDA once to handle totals efficiently
        $allMdaSubheads = Subhead::where('mda_id', $this->selectedMdaId)
            ->with('category')
            ->get();

        // 3. Calculate "Assigned Portfolio Total" ($mdaTotal)
        $mdaTotal = $allMdaSubheads->sum(function($s) {
            return (float)$s->approved_provision + (float)$s->additional_provision;
        });

        // 4. Build the Category Totals array for the Tabs
        $categoryList = ['Revenue', 'Personnel', 'Overhead', 'Capital'];
        $categoryTotals = array_fill_keys($categoryList, 0);

        foreach ($allMdaSubheads as $subhead) {
            $type = $subhead->category->type ?? '';
            if (isset($categoryTotals[$type])) {
                $categoryTotals[$type] += ((float)$subhead->approved_provision + (float)$subhead->additional_provision);
            }
        }

        // 5. Filter subheads for the table based on active tab
        $subheads = $allMdaSubheads->filter(function($s) {
            return ($s->category->type ?? '') === $this->activeCategory;
        })->sortBy('subhead_code');

        // 6. Return the view with variables matching your subhead-show.blade.php
        return view('livewire.officer.subheads.subhead-show', [ 
            'mda' => $mda,
            'subheads' => $subheads,
            'mdaTotal' => $mdaTotal,           // Fixed: Was $mdaCategoryTotal
            'categoryTotals' => $categoryTotals // Fixed: Was $globalCategoryTotal
        ]);
    }
}