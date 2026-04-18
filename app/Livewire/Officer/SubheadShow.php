<?php

namespace App\Livewire\Officer; // 1. Updated Namespace

use Livewire\Component;
use App\Models\Mda;
use App\Models\Subhead;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;

class SubheadShow extends Component
{
    public Mda $mda;

    #[Url(keep: true)]
    public $activeCategory = 'Revenue'; 

    public function mount(Mda $mda)
    {
        // 2. SECURITY: Hard Wall. 
        // Ensure this officer is actually assigned to this MDA
        if (!Auth::user()->mdas()->where('id', $mda->id)->exists()) {
            abort(403, 'Unauthorized access to this MDA.');
        }

        $this->mda = $mda;
    }

    public function setCategory($categoryName)
    {
        $this->activeCategory = $categoryName;
    }

    public function render()
    {
        // 3. SCOPING: Fetch subheads only for the validated MDA
        $allMdaSubheads = Subhead::where('mda_id', $this->mda->id)
            ->with('category')
            ->get();
        
        $mdaTotal = $allMdaSubheads->sum->total_budget;
        
        $categoryList = ['Revenue', 'Personnel', 'Overhead', 'Capital'];
        $categoryTotals = array_fill_keys($categoryList, 0);

        foreach ($allMdaSubheads as $subhead) {
            $dbCatType = trim($subhead->category->type ?? '');
            
            foreach ($categoryList as $standardType) {
                if (strcasecmp($dbCatType, $standardType) === 0) {
                    $categoryTotals[$standardType] += (float)$subhead->total_budget;
                }
            }
        }

        $subheads = $allMdaSubheads->filter(function($s) {
            $dbType = trim($s->category->type ?? '');
            return strcasecmp($dbType, $this->activeCategory) === 0;
        })->sortBy('subhead_code');

        // 4. VIEW PATH: Point to the officer directory
        return view('livewire.officer.subheads.subhead-show', [
            'subheads' => $subheads,
            'mdaTotal' => $mdaTotal,
            'categoryTotals' => $categoryTotals
        ])->layout('layouts.app'); // Or layouts.app, depending on your officer theme
    }
}