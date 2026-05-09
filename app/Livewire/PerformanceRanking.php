<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BudgetAnalyticsService;
use Illuminate\Support\Collection;

class PerformanceRanking extends Component
{
    /**
     * Consolidated Filter State
     * Ranks can be filtered by type, category, quarter, and the ranking metric itself.
     */
    public $filters = [
        'type'     => 'expenditure', // Options: expenditure, revenue
        'category' => 'all',         // Options: all, PERSONNEL, OVERHEAD, CAPITAL
        'quarter'  => 'all',         // Options: all, 1, 2, 3, 4
        'viewMode' => 'spending'     // Options: spending (Volume), utilization (Performance %)
    ];

    /**
     * Lifecycle hook: updatedFilters
     * Ensures logical consistency when the user changes filter values.
     */
    public function updatedFilters($value, $key)
    {
        // If switching to Revenue, sub-categories like Personnel/Capital don't apply.
        if ($key === 'type' && $value === 'revenue') {
            $this->filters['category'] = 'all';
        }
    }

    /**
     * Main render cycle.
     * Uses the BudgetAnalyticsService to process data based on structural subhead rules.
     */
    public function render(BudgetAnalyticsService $service)
    {
        // 1. Prepare constraints based on your specific mapping:
        // PERSONNEL: 8 digits, starts with 21
        // OVERHEAD:  8 digits, starts with 22
        // REVENUE:   8 digits, starts with 11, 12, 13, or 14
        // CAPITAL:   10 digits, any starting number
        $constraints = [
            'type'    => $this->filters['type'],
            'quarter' => $this->filters['quarter'],
            'rules'   => $this->resolveSubheadRules()
        ];

        // 2. Fetch data from the service. 
        // Service method must handle the LENGTH() and PREFIX logic in SQL.
        $rankings = $service->getRankingsBySubheadLogic($constraints);

        // 3. Apply Sorting Logic based on the selected viewMode filter.
        $sortedData = ($this->filters['viewMode'] === 'spending') 
            ? $rankings->sortByDesc('actual') 
            : $rankings->sortByDesc('performance_percentage');

        return view('livewire.performance-ranking', [
            'mdaData' => $sortedData->values()
        ]);
    }

    /**
     * Maps the UI categories to the structural database rules.
     */
    private function resolveSubheadRules()
    {
        // Revenue logic: specific 8-digit prefixes
        if ($this->filters['type'] === 'revenue') {
            return [
                'length'   => 8,
                'prefixes' => ['11', '12', '13', '14']
            ];
        }

        // Expenditure logic: personnel/overhead (8-digit) vs capital (10-digit)
        return match (strtoupper($this->filters['category'])) {
            'PERSONNEL' => [
                'length'   => 8, 
                'prefixes' => ['21']
            ],
            'OVERHEAD'  => [
                'length'   => 8, 
                'prefixes' => ['22']
            ],
            'CAPITAL'   => [
                'length'   => 10, 
                'prefixes' => [] // Service treats as "match any prefix for 10 digits"
            ],
            // 'all' excludes 8-digit codes starting with 11-14 to keep revenue out
            default => 'all_expenditure' 
        };
    }
}