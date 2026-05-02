<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Subhead;
use App\Models\Release;
use App\Models\Setting;
use App\Models\Mda; // Added this
use App\Services\BudgetPerformanceService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class BudgetPerformance extends Component
{
    public $quarter = 1;
    public $categoryId = null; // This will now store the master type (e.g., 'OVERHEAD')
    public $reportType = 'executive'; 
    
    protected $service;

    public function boot(BudgetPerformanceService $service)
    {
        $this->service = $service;
    }

    /**
     * Replaced the dynamic category fetch with the 4 Master Categories
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

    public function updatedReportType()
    {
        if ($this->reportType !== 'detailed') {
            $this->categoryId = null;
        }
    }

    public function render()
    {
        $results = [];
        $settings = Setting::first();
        $year = $settings->fiscal_year ?? date('Y');

        if ($this->reportType === 'executive') {
            $results = $this->getExecutiveTableData($year);
        } 
        elseif ($this->reportType === 'overview') {
            $results = $this->getQuarterlySummaryData($year, $this->quarter);
        } 
        elseif ($this->reportType === 'detailed' && $this->categoryId) {
            // Updated to use the new grouping logic
            $results = $this->getDetailedGroupedReport($year, $this->quarter, $this->categoryId);
        } 
        // elseif ($this->reportType === 'ranking') {
        //     $results = [
        //         'top' => $this->service->getRankingReport($this->quarter, 10, 'desc'),
        //         'least' => $this->service->getRankingReport($this->quarter, 10, 'asc'),
        //     ];
        // }
        elseif ($this->reportType === 'ranking') {
            $results = [
                'full_list' => $this->service->getRankingReport($this->quarter, null, 'desc'),
            ];
        }
        return view('livewire.admin.budget-performance', [
            'results' => $results,
            'year' => $year
        ])->layout('layouts.app');
    }

    /**
     * NEW: Fetches all MDAs and their subheads filtered by the master category type
     */
    private function getDetailedGroupedReport($year, $quarter, $masterType)
    {
        $periods = [
            1 => ["$year-01-01", "$year-03-31"],
            2 => ["$year-04-01", "$year-06-30"],
            3 => ["$year-07-01", "$year-09-30"],
            4 => ["$year-10-01", "$year-12-31"],
        ];

        $start = $periods[$quarter][0];
        $end   = $periods[$quarter][1];

        // 1. Get all category IDs matching the master type
        $keywords = ($masterType === 'OVERHEAD') ? ['OVERHEAD', 'RECURRENT'] : [$masterType];
        
        $categoryIds = Category::where(function($q) use ($keywords) {
            foreach ($keywords as $key) {
                $q->orWhere('type', 'LIKE', '%' . $key . '%');
            }
        })->pluck('id');

        // 2. Fetch MDAs with their nested subheads and strictly scoped release sums
        return Mda::with(['subheads' => function($q) use ($categoryIds, $start, $end) {
            $q->whereIn('category_id', $categoryIds)
            /** * FIX 1: Explicitly name the sum result 'releases_sum_amount'.
             * FIX 2: Use whereDate to ensure strict database comparison.
             * FIX 3: Ensure the relationship is scoped to the specific subhead.
             */
            ->withSum(['releases as releases_sum_amount' => function($sq) use ($start, $end) {
                $sq->whereDate('release_date', '>=', $start)
                    ->whereDate('release_date', '<=', $end);
            }], 'amount');
        }])
        // Only return MDAs that actually have subheads in these categories
        ->whereHas('subheads', function($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        })
        ->get();
    }

    private function getExecutiveTableData($year)
    {
        $segments = [
            ['label' => 'Revenue',        'keywords' => ['REVENUE']],
            ['label' => 'Personnel Cost', 'keywords' => ['PERSONNEL']],
            ['label' => 'Overhead Cost',  'keywords' => ['OVERHEAD', 'RECURRENT']],
            ['label' => 'Capital Exp.',   'keywords' => ['CAPITAL']],
        ];

        $data = [];
        foreach ($segments as $segment) {
            $categoryIds = Category::where(function($q) use ($segment) {
                foreach ($segment['keywords'] as $key) {
                    $q->orWhere('type', 'LIKE', '%' . $key . '%');
                }
            })->pluck('id')->toArray();

            if (empty($categoryIds)) {
                $data[] = $this->formatEmptyRow($segment['label']);
                continue;
            }

            $approvedSum = Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision');
            $additionalSum = Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
            $budget = $approvedSum + $additionalSum;
            
            $q1 = $this->getMultiCategorySum($categoryIds, "$year-01-01", "$year-03-31");
            $q2 = $this->getMultiCategorySum($categoryIds, "$year-04-01", "$year-06-30");
            $q3 = $this->getMultiCategorySum($categoryIds, "$year-07-01", "$year-09-30");
            $q4 = $this->getMultiCategorySum($categoryIds, "$year-10-01", "$year-12-31");
            
            $totalActual = $q1 + $q2 + $q3 + $q4;

            $data[] = [
                'label'    => $segment['label'],
                'total_prov' => $budget,
                'q1'       => $q1,
                'q2'       => $q2,
                'q3'       => $q3,
                'q4'       => $q4,
                'total'    => $totalActual,
                'perf'     => $budget > 0 ? ($totalActual / $budget) * 100 : 0
            ];
        }
        return $data;
    }

    private function getMultiCategorySum($categoryIds, $start, $end)
    {
        return Release::whereHas('subhead', function($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->whereDate('release_date', '>=', $start)
            ->whereDate('release_date', '<=', $end)
            ->sum('amount');
    }

    private function formatEmptyRow($label) {
        return [
            'label' => $label . ' (Not Found)',
            'approved' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'perf' => 0
        ];
    }

    public function export($format)
    {
        return redirect()->route('admin.performance.export', [
            'q'      => $this->quarter,
            'type'   => $this->reportType,
            'cat'    => $this->categoryId, // Will pass 'OVERHEAD', 'CAPITAL', etc.
            'format' => $format
        ]);
    }

    private function getQuarterlySummaryData($year, $quarter)
    {
        $periods = [
            1 => ["$year-01-01", "$year-03-31"],
            2 => ["$year-04-01", "$year-06-30"],
            3 => ["$year-07-01", "$year-09-30"],
            4 => ["$year-10-01", "$year-12-31"],
        ];
        
        $qStart = $periods[$quarter][0];
        $qEnd   = $periods[$quarter][1];

        $segments = [
            ['label' => 'Revenue Performance', 'keywords' => ['REVENUE']],
            ['label' => 'Personnel Cost',      'keywords' => ['PERSONNEL']],
            ['label' => 'Recurrent Overhead',  'keywords' => ['OVERHEAD', 'RECURRENT']],
            ['label' => 'Capital Expenditure', 'keywords' => ['CAPITAL']],
        ];

        $summary = [];
        foreach ($segments as $segment) {
            $categoryIds = Category::where(function($q) use ($segment) {
                foreach ($segment['keywords'] as $key) {
                    $q->orWhere('type', 'LIKE', '%' . $key . '%');
                }
            })->pluck('id')->toArray();

            $approved   = Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision');
            $additional = Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
            $totalProv  = $approved + $additional;

            $actualQuarterly = $this->getMultiCategorySum($categoryIds, $qStart, $qEnd);
            $actualYTD = $this->getMultiCategorySum($categoryIds, "$year-01-01", $qEnd);

            // $quarterlyTarget = $totalProv / 4;
            // $perf = $quarterlyTarget > 0 ? ($actualQuarterly / $quarterlyTarget) * 100 : 0;
            
            // CHANGE THIS: 
            // Use $totalProv instead of $quarterlyTarget if you want annual relative performance
            $perf = $totalProv > 0 ? ($actualQuarterly / $totalProv) * 100 : 0;


            $summary[] = [
                'label'      => $segment['label'],
                'approved'   => $approved,
                'additional' => $additional,
                'total_prov' => $totalProv,
                'actual'     => $actualQuarterly,
                'ytd_actual' => $actualYTD,
                'balance'    => $totalProv - $actualYTD,
                'perf'       => $perf
            ];
        }

        return $summary;
    }
}