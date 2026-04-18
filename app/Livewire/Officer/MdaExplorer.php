<?php

namespace App\Livewire\Officer;

use Livewire\Component;
use App\Models\Mda;
use Illuminate\Support\Facades\Auth;

class MdaExplorer extends Component
{
    public $selectedMdaId;
    public $activeCategory = 'Personnel'; 

    /**
     * Captures the MDA ID from the Dashboard link
     */
    public function mount($selectedMdaId = null)
    {
        if ($selectedMdaId) {
            // Security Check: Ensure the user actually owns this MDA
            $exists = Mda::where('id', $selectedMdaId)
                         ->where('user_id', Auth::id())
                         ->exists();
            
            if ($exists) {
                $this->selectedMdaId = $selectedMdaId;
            }
        }
    }

    public function selectMda($id)
    {
        $this->selectedMdaId = $id;
    }

    public function setCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        $mdas = Mda::where('user_id', Auth::id())->get();
        $currentMda = null;
        $stats = [];
        $filteredSubheads = [];

        foreach (['Revenue', 'Personnel', 'Overhead', 'Capital'] as $cat) {
            $stats[$cat] = ['provision' => 0, 'performance' => 0];
        }

        if ($this->selectedMdaId) {
            // We load subheads with their category and the SUM of their releases
            $currentMda = Mda::with(['subheads' => function($query) {
                $query->withSum('releases', 'amount'); // This calculates subhead performance
            }, 'subheads.category'])
            ->where('user_id', Auth::id())
            ->find($this->selectedMdaId);

            if ($currentMda) {
                foreach (['Revenue', 'Personnel', 'Overhead', 'Capital'] as $cat) {
                    $catSubheads = $currentMda->subheads->filter(function($subhead) use ($cat) {
                        return $subhead->category && $subhead->category->type === $cat;
                    });
                    
                    $stats[$cat] = [
                        'provision' => $catSubheads->sum(fn($s) => $s->approved_provision + $s->additional_provision),
                        'performance' => $catSubheads->sum('releases_sum_amount') ?? 0,
                    ];
                }

                $filteredSubheads = $currentMda->subheads->filter(function($subhead) {
                    return $subhead->category && $subhead->category->type === $this->activeCategory;
                });
            }
        }

        return view('livewire.officer.mda-explorer', [
            'mdas' => $mdas,
            'currentMda' => $currentMda,
            'stats' => $stats,
            'filteredSubheads' => $filteredSubheads
        ]);
    }
}