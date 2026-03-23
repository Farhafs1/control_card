<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Mda;
use App\Models\Subhead;
use Livewire\Attributes\Url;

class SubheadShow extends Component
{
    public Mda $mda;

    #[Url(keep: true)]
    public $activeCategory = 'Revenue'; 

    public function mount(Mda $mda)
    {
        $this->mda = $mda;
    }

    public function setCategory($categoryName)
    {
        $this->activeCategory = $categoryName;
    }

    public function render()
    {
        // 1. Fetch all subheads for this MDA with category relationship
        // We use eager loading 'category' to prevent N+1 issues
        $allMdaSubheads = Subhead::where('mda_id', $this->mda->id)
            ->with('category')
            ->get();
        
        // 2. Calculate the global MDA Total using your model's attribute
        $mdaTotal = $allMdaSubheads->sum->total_budget;
        
        // 3. Define standard categories matching your Migration 'type' column
        $categoryList = [
            'Revenue', 
            'Personnel', 
            'Overhead', 
            'Capital'
        ];

        $categoryTotals = array_fill_keys($categoryList, 0);

        foreach ($allMdaSubheads as $subhead) {
            // Using 'type' as defined in your category migration
            $dbCatType = trim($subhead->category->type ?? '');
            
            foreach ($categoryList as $standardType) {
                // Case-insensitive match to ensure 'revenue' matches 'Revenue'
                if (strcasecmp($dbCatType, $standardType) === 0) {
                    $categoryTotals[$standardType] += (float)$subhead->total_budget;
                }
            }
        }

        // 4. Filter for the Table
        // We use the same strcasecmp logic here to ensure if the sum works, the table works
        $subheads = $allMdaSubheads->filter(function($s) {
            $dbType = trim($s->category->type ?? '');
            return strcasecmp($dbType, $this->activeCategory) === 0;
        })->sortBy('subhead_code');

        return view('livewire.admin.subheads.subhead-show', [
            'subheads' => $subheads,
            'mdaTotal' => $mdaTotal,
            'categoryTotals' => $categoryTotals
        ])->layout('layouts.app');
    }
}