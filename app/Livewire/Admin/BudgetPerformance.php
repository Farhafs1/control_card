<?php

namespace App\Livewire\Admin;

use App\Models\{Subhead, Release, Setting, Mda};
use App\Services\BudgetPerformanceService;
use Livewire\{Component, Attributes\Computed};

class BudgetPerformance extends Component
{
    public $categoryId = null; // Stores master type keyword (e.g., 'OVERHEAD')
    public $reportType = 'executive';
    public $quarter = 'all'; 
    
    protected $service;

    /**
     * Dependency injection via boot method.
     */
    public function boot(BudgetPerformanceService $service)
    {
        $this->service = $service;
    }

    /**
     * Computed property to map Master Types to display labels.
     */
    #[Computed]
    public function masterCategories()
    {
        return [
            'REVENUE'   => 'Revenue Performance',
            'PERSONNEL' => 'Personnel Cost',
            'OVERHEAD'  => 'Recurrent Overhead',
            'CAPITAL'   => 'Capital Expenditure',
        ];
    }

    /**
     * Resets dependent filters when report view swaps.
     */
    public function updatedReportType()
    {
        if ($this->reportType !== 'detailed') {
            $this->categoryId = null;
        }
    }

    /**
     * Livewire Render Lifecycle Method.
     */
    public function render()
    {
        $settings = Setting::first();
        $year = $settings->fiscal_year ?? date('Y');

        $results = match ($this->reportType) {
            'executive' => $this->getExecutiveTableData($year),
            'overview'  => $this->getQuarterlySummaryData($year, $this->quarter),
            'detailed'  => $this->categoryId ? $this->getDetailedGroupedReport($year, $this->quarter, $this->categoryId) : [],
            'ranking'   => ['full_list' => $this->service->getRankingReport($this->quarter, null, 'desc')],
            default     => [],
        };

        return view('livewire.admin.budget-performance', [
            'results' => $results,
            'year'    => $year
        ])->layout('layouts.app');
    }

    /**
     * Resolves Master Type keywords into array of Subhead IDs based on standard Chart of Accounts rules.
     * * @param string $masterType
     * @return array
     */
    private function getSubheadIds($masterType)
    {
        // Adjust this to match your actual schema (e.g., 'subhead_code', 'item_code')
        $codeColumn = 'subhead_code'; 

        $query = Subhead::query();

        if ($masterType === 'CAPITAL') {
            // Safe binding expression avoiding string parsing bugs across engines
            return $query->whereRaw("LENGTH({$codeColumn}) > 8")->pluck('id')->toArray();
        }

        $query->whereRaw("LENGTH({$codeColumn}) = 8");

        switch ($masterType) {
            case 'REVENUE':
                $query->where($codeColumn, 'LIKE', '1%');
                break;

            case 'PERSONNEL':
                $query->where($codeColumn, 'LIKE', '21%');
                break;

            case 'OVERHEAD':
                $query->where($codeColumn, 'LIKE', '22%');
                break;
                
            default:
                return [];
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * Computes provision data aggregates by subhead IDs.
     * * @param array $subheadIds
     * @return array
     */
    private function getProvisionData($subheadIds)
    {
        if (empty($subheadIds)) {
            return ['approved' => 0, 'additional' => 0, 'total' => 0];
        }

        $approved = Subhead::whereIn('id', $subheadIds)->sum('approved_provision');
        $additional = Subhead::whereIn('id', $subheadIds)->sum('additional_provision');
        
        return [
            'approved'   => $approved,
            'additional' => $additional,
            'total'      => $approved + $additional
        ];
    }

    /**
     * Fetches detailed grouped breakdown filtered and summarized by MDA allocations.
     */
    private function getDetailedGroupedReport($year, $quarter, $masterType)
    {
        $subheadIds = $this->getSubheadIds($masterType);
        $numericQuarter = ($quarter === 'all') ? 4 : (int)$quarter;

        if (empty($subheadIds)) {
            return collect();
        }

        return Mda::with(['subheads' => function($q) use ($subheadIds, $numericQuarter, $quarter) {
            $q->whereIn('id', $subheadIds)
            ->withSum(['releases as releases_sum_amount' => function($sq) use ($numericQuarter, $quarter) {
                if ($quarter === 'all') {
                    $sq->where('quarter', '<=', 4); 
                } else {
                    $sq->where('quarter', '=', $numericQuarter); 
                }
            }], 'amount');
        }])
        ->whereHas('subheads', function($q) use ($subheadIds) {
            $q->whereIn('id', $subheadIds);
        })
        ->get();
    }

    /**
     * Generates data for the primary Executive Overview dashboard view.
     * Optimized using database conditional raw expressions to eliminate the 30-second local timeout wall.
     */
    private function getExecutiveTableData($year)
    {
        $segments = [
            ['label' => 'Revenue',        'type' => 'REVENUE'],
            ['label' => 'Personnel Cost', 'type' => 'PERSONNEL'],
            ['label' => 'Overhead Cost',  'type' => 'OVERHEAD'],
            ['label' => 'Capital Exp.',   'type' => 'CAPITAL'],
        ];

        $data = [];
        foreach ($segments as $segment) {
            $subheadIds = $this->getSubheadIds($segment['type']);

            if (empty($subheadIds)) {
                $data[] = $this->formatEmptyRow($segment['label']);
                continue;
            }

            $provisions = $this->getProvisionData($subheadIds);
            
            // Performance Optimization: Fetch sums of all 4 quarters in a single high-performance query
            $quarterSums = Release::whereIn('subhead_id', $subheadIds)
                ->selectRaw("
                    SUM(CASE WHEN quarter = 1 THEN amount ELSE 0 END) as q1,
                    SUM(CASE WHEN quarter = 2 THEN amount ELSE 0 END) as q2,
                    SUM(CASE WHEN quarter = 3 THEN amount ELSE 0 END) as q3,
                    SUM(CASE WHEN quarter = 4 THEN amount ELSE 0 END) as q4
                ")
                ->first();

            $qs = [
                'q1' => (float)($quarterSums->q1 ?? 0),
                'q2' => (float)($quarterSums->q2 ?? 0),
                'q3' => (float)($quarterSums->q3 ?? 0),
                'q4' => (float)($quarterSums->q4 ?? 0),
            ];
            
            $totalActual = array_sum($qs);

            $data[] = array_merge([
                'label'      => $segment['label'],
                'total_prov' => $provisions['total'],
                'total'      => $totalActual,
                'perf'       => $provisions['total'] > 0 ? ($totalActual / $provisions['total']) * 100 : 0
            ], $qs);
        }
        return $data;
    }

    /**
     * Direct query wrapper helper to fetch basic transaction aggregates across subheads.
     */
    private function getMultiCategorySum($subheadIds, $quarter)
    {
        if (empty($subheadIds)) {
            return 0;
        }

        return Release::whereIn('subhead_id', $subheadIds)
            ->when($quarter !== 'all', function($query) use ($quarter) {
                return $query->where('quarter', $quarter);
            })
            ->sum('amount');
    }

    /**
     * Fallback format engine helper for missing structures.
     */
    private function formatEmptyRow($label) {
        return [
            'label' => $label . ' (Not Found)',
            'total_prov' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'perf' => 0
        ];
    }

    /**
     * Routes format parameters to export processors.
     */
    public function export($format)
    {
        return redirect()->route('admin.performance.export', [
            'q'      => $this->quarter,
            'type'   => $this->reportType,
            'cat'    => $this->categoryId, 
            'format' => $format
        ]);
    }

    /**
     * Generates dataset for the single quarter perspective report.
     */
    private function getQuarterlySummaryData($year, $quarter)
    {
        $segments = [
            ['label' => 'Revenue Performance', 'type' => 'REVENUE'],
            ['label' => 'Personnel Cost',       'type' => 'PERSONNEL'],
            ['label' => 'Recurrent Overhead',   'type' => 'OVERHEAD'],
            ['label' => 'Capital Expenditure', 'type' => 'CAPITAL'],
        ];

        $summary = [];
        foreach ($segments as $segment) {
            $subheadIds = $this->getSubheadIds($segment['type']);
            $provisions = $this->getProvisionData($subheadIds);

            $actualQuarterly = $this->getMultiCategorySum($subheadIds, $quarter);
            
            // Sum Year-To-Date calculations up to selected target period
            $actualYTD = Release::whereIn('subhead_id', $subheadIds)
                ->when($quarter !== 'all', function($q) use ($quarter) {
                    return $q->where('quarter', '<=', $quarter);
                })
                ->sum('amount');

            $summary[] = [
                'label'      => $segment['label'],
                'approved'   => $provisions['approved'],
                'additional' => $provisions['additional'],
                'total_prov' => $provisions['total'],
                'actual'     => $actualQuarterly,
                'ytd_actual' => $actualYTD,
                'balance'    => $provisions['total'] - $actualYTD,
                'perf'       => $provisions['total'] > 0 ? ($actualQuarterly / $provisions['total']) * 100 : 0
            ];
        }

        return $summary;
    }
}