<?php

namespace App\Livewire\Admin;

use App\Models\{Category, Subhead, Release, Setting, Mda};
use App\Services\BudgetPerformanceService;
use Livewire\{Component, Attributes\Computed};

class BudgetPerformance extends Component
{
    public $categoryId = null; // Stores master type (e.g., 'OVERHEAD')
    public $reportType = 'executive';
    public $quarter = 'all'; // Change from 1 to 'all' 
    
    protected $service;

    public function boot(BudgetPerformanceService $service)
    {
        $this->service = $service;
    }

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
     * Helper to resolve Master Type keywords to Category IDs
     */
    private function getCategoryIds($masterType)
    {
        $keywords = ($masterType === 'OVERHEAD') ? ['OVERHEAD', 'RECURRENT'] : [$masterType];
        
        return Category::where(function($q) use ($keywords) {
            foreach ($keywords as $key) {
                $q->orWhere('type', 'LIKE', '%' . $key . '%');
            }
        })->pluck('id')->toArray();
    }

    /**
     * Helper to get Provision sums for a set of categories
     */
    private function getProvisionData($categoryIds)
    {
        $approved = Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision');
        $additional = Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
        
        return [
            'approved'   => $approved,
            'additional' => $additional,
            'total'      => $approved + $additional
        ];
    }

    private function getDetailedGroupedReport($year, $quarter, $masterType)
    {
        $categoryIds = $this->getCategoryIds($masterType);

        // Standardize 'all' to 4 for the comparison logic
        $numericQuarter = ($quarter === 'all') ? 4 : (int)$quarter;

        return Mda::with(['subheads' => function($q) use ($categoryIds, $numericQuarter, $quarter) {
            $q->whereIn('category_id', $categoryIds)
            ->withSum(['releases as releases_sum_amount' => function($sq) use ($numericQuarter, $quarter) {
                
                if ($quarter === 'all') {
                    // For "All", sum every release from Q1 to Q4
                    $sq->where('quarter', '<=', 4); 
                } else {
                    // For a specific quarter, get ONLY that quarter
                    $sq->where('quarter', '=', $numericQuarter); 
                }

            }], 'amount');
        }])
        ->whereHas('subheads', function($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        })
        ->get();
    }

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
            $categoryIds = $this->getCategoryIds($segment['type']);

            if (empty($categoryIds)) {
                $data[] = $this->formatEmptyRow($segment['label']);
                continue;
            }

            $provisions = $this->getProvisionData($categoryIds);
            
            $qs = [];
            for ($i = 1; $i <= 4; $i++) {
                $qs["q$i"] = $this->getMultiCategorySum($categoryIds, $i);
            }
            
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

    private function getMultiCategorySum($categoryIds, $quarter)
    {
        return Release::whereHas('subhead', function($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->when($quarter !== 'all', function($query) use ($quarter) {
                return $query->where('quarter', $quarter);
            })
            ->sum('amount');
    }

    private function formatEmptyRow($label) {
        return [
            'label' => $label . ' (Not Found)',
            'total_prov' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'perf' => 0
        ];
    }

    public function export($format)
    {
        return redirect()->route('admin.performance.export', [
            'q'      => $this->quarter,
            'type'   => $this->reportType,
            'cat'    => $this->categoryId, 
            'format' => $format
        ]);
    }

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
            $categoryIds = $this->getCategoryIds($segment['type']);
            $provisions = $this->getProvisionData($categoryIds);

            $actualQuarterly = $this->getMultiCategorySum($categoryIds, $quarter);
            
            $actualYTD = Release::whereHas('subhead', function($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })
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