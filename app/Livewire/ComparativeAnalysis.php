<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BudgetAnalyticsService;

class ComparativeAnalysis extends Component
{
    /**
     * 'spending' sorts by total actual releases.
     * 'efficiency' sorts by the weighted_score (Utilization vs Scale).
     */
    public $viewMode = 'spending'; 
    
    public $filters = [
        'quarter' => 'all'
    ];

    /**
     * Listener for filter changes to ensure the component re-renders
     * when quarters (e.g., 2nd Term/Q2) are switched.
     */
    public function updatedFilters()
    {
        // This triggers a re-render automatically
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function render(BudgetAnalyticsService $service)
    {
        // 1. Fetch the collection from your updated Service method
        $rankings = $service->getComparativeRankings($this->filters['quarter']);

        /** 
         * 2. Sort based on the Toggle UI
         * We use sortByDesc on the Eloquent collection returned by the service.
         * 'actual' corresponds to the raw release amount.
         * 'weighted_score' corresponds to our performance/scale balance logic.
         */
        $data = ($this->viewMode === 'spending') 
            ? $rankings->sortByDesc('actual') 
            : $rankings->sortByDesc('weighted_score');

        return view('livewire.comparative-analysis', [
            // .values() is essential here to reset array keys after sorting,
            // preventing JavaScript/Livewire hydration issues in the frontend loop.
            'mdaData' => $data->values()
        ]);
    }
}