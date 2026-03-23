<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\Subhead;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Subheads extends Component
{
    public $selectedMdaId = null;
    public $activeCategory = 'Personnel'; // Default category

    public function selectMda($id)
    {
        $this->selectedMdaId = $id;
    }

    public function resetSelection()
    {
        $this->selectedMdaId = null;
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
        $mdas = Mda::withSum('subheads as total_provision', DB::raw('approved_provision + additional_provision'))
            ->get();

        return view('livewire.admin.subheads.index', [
            'mdas' => $mdas
        ]);
    }

    private function renderDetailView()
    {
        $mda = Mda::findOrFail($this->selectedMdaId);
        
        // Category Totals for this MDA
        $mdaCategoryTotal = Subhead::where('mda_id', $this->selectedMdaId)
            ->whereHas('category', fn($q) => $q->where('type', $this->activeCategory))
            ->sum(DB::raw('approved_provision + additional_provision'));

        // Global Category Total (for percentage calculation)
        $globalCategoryTotal = Subhead::whereHas('category', fn($q) => $q->where('type', $this->activeCategory))
            ->sum(DB::raw('approved_provision + additional_provision'));

        $subheads = Subhead::where('mda_id', $this->selectedMdaId)
            ->whereHas('category', fn($q) => $q->where('type', $this->activeCategory))
            ->get();

        return view('livewire.admin.subheads.detail', [
            'mda' => $mda,
            'subheads' => $subheads,
            'mdaCategoryTotal' => $mdaCategoryTotal,
            'globalCategoryTotal' => $globalCategoryTotal,
            'percentage' => $globalCategoryTotal > 0 ? ($mdaCategoryTotal / $globalCategoryTotal) * 100 : 0
        ]);
    }
}