<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BudgetPerformanceService;
use App\Models\Subhead;
use App\Models\Release;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Mda; // Added for grouped export
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PerformanceExportController extends Controller
{
    protected $service;

    public function __construct(BudgetPerformanceService $service)
    {
        $this->service = $service;
    }

    public function export(Request $request)
    {
        // Add these two lines at the very start
        ini_set('memory_limit', '512M'); 
        ini_set('max_execution_time', '300'); // 5 minutes

        $quarter = $request->q ?? 1;
        $type = $request->type ?? 'overview';
        $categoryKeyword = $request->cat; // Now a string like 'OVERHEAD'
        $format = $request->format ?? 'pdf';
        $year = Setting::first()->fiscal_year ?? date('Y');

        // 1. Executive Case
        if ($type === 'executive') {
            return $this->exportExecutiveOverview($year);
        }

        // 2. Overview Case 
        if ($type === 'overview') {
            return $this->exportQuarterlyOverview($year, $quarter);
        }

        // 3. Detailed Case (Grouped by MDA)
        if ($type === 'detailed') {
            $data = $this->getDetailedGroupedData($year, $quarter, $categoryKeyword);
        } else {
            // --- Ranking Case Fix ---
            // We fetch both for CSV/Other logic, but we must ensure 
            // the PDF 'results' variable gets a flat array of MDAs.
            $rankingData = $this->service->getRankingReport($quarter, 50, 'desc'); 
            
            if ($format === 'csv') {
                $data = [
                    'top' => $rankingData,
                    'least' => $this->service->getRankingReport($quarter, 10, 'asc'),
                ];
            } else {
                // For PDF, we pass the ranking list directly so $results is a loopable array
                $data = $rankingData;
            }
        }

        if ($format === 'csv') {
            return $this->downloadCsv($data, $type, $quarter);
        }

        $pdf = Pdf::loadView("pdf.performance.{$type}", [
            'results' => $data, // Now correctly contains the array of MDAs
            'quarter' => $quarter,
            'date'    => date('d M, Y'),
            'year'    => $year,
            'categoryName' => $categoryKeyword 
        ])->setPaper('a4', 'landscape');

        return $pdf->download("KTSG_Performance_Q{$quarter}_{$type}.pdf");
    }
    /**
     * Internal Logic to match the Livewire Component's detailed grouping
     */
    private function getDetailedGroupedData($year, $quarter, $masterType)
    {
        $periods = [
            1 => ["$year-01-01", "$year-03-31"],
            2 => ["$year-04-01", "$year-06-30"],
            3 => ["$year-07-01", "$year-09-30"],
            4 => ["$year-10-01", "$year-12-31"],
        ];

        $start = $periods[$quarter][0];
        $end   = $periods[$quarter][1];

        $keywords = ($masterType === 'OVERHEAD') ? ['OVERHEAD', 'RECURRENT'] : [$masterType];
        
        $categoryIds = Category::where(function($q) use ($keywords) {
            foreach ($keywords as $key) {
                $q->orWhere('type', 'LIKE', '%' . $key . '%');
            }
        })->pluck('id');

        // FIX: Using withSum properly on the relationship
        return Mda::with(['subheads' => function($q) use ($categoryIds, $start, $end) {
            $q->whereIn('category_id', $categoryIds)
            ->withSum(['releases as releases_sum_amount' => function($sq) use ($start, $end) {
                // Ensure we are filtering by date AND the subhead_id match is handled by Eloquent
                $sq->whereDate('release_date', '>=', $start)
                    ->whereDate('release_date', '<=', $end);
            }], 'amount');
        }])
        ->whereHas('subheads', function($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        })
        ->get();
    }

    private function exportExecutiveOverview($year)
    {
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

            if (empty($categoryIds)) {
                $summary[] = ['label' => $segment['label'], 'approved' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'perf' => 0];
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

            $summary[] = [
                'label'         => $segment['label'],
                'total_prov'    => $budget,
                'q1'            => $q1, 'q2' => $q2, 'q3' => $q3, 'q4' => $q4,
                'total'         => $totalActual,
                'perf'          => $budget > 0 ? ($totalActual / $budget) * 100 : 0
            ];
        }

        $pdf = Pdf::loadView('pdf.performance.executive', [
            'summary' => $summary,
            'year'    => $year,
            'date'    => date('d M, Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Executive_Budget_Overview_{$year}.pdf");
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

    private function downloadCsv($data, $type, $quarter)
    {
        $filename = "Performance_Q{$quarter}_{$type}.csv";
        
        $callback = function() use ($data, $type) {
            $handle = fopen('php://output', 'w');
            
            if ($type === 'detailed') {
                fputcsv($handle, ['MDA', 'Subhead', 'Approved Provision', 'Additional Provision', 'Total Budget', 'Actual Qtr Release', 'Performance %']);
                foreach ($data as $mda) {
                    foreach ($mda->subheads as $sub) {
                        $total = $sub->approved_provision + $sub->additional_provision;
                        $actual = $sub->releases_sum_amount ?? 0;
                        $perf = $total > 0 ? ($actual / ($total / 4)) * 100 : 0;
                        fputcsv($handle, [$mda->name, $sub->name, $sub->approved_provision, $sub->additional_provision, $total, $actual, round($perf, 2)]);
                    }
                }
            } elseif ($type === 'overview') {
                fputcsv($handle, ['Category', 'Provision', 'Actual', 'Performance %']);
                foreach ($data as $row) {
                    fputcsv($handle, [$row['label'], $row['total_prov'], $row['actual'], $row['perf']]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportQuarterlyOverview($year, $quarter)
    {
        $periods = [
            1 => ["$year-01-01", "$year-03-31"],
            2 => ["$year-04-01", "$year-06-30"],
            3 => ["$year-07-01", "$year-09-30"],
            4 => ["$year-10-01", "$year-12-31"],
        ];

        $qStart = $periods[$quarter][0];
        $qEnd   = $periods[$quarter][1];
        $ytdStart = "$year-01-01";

        $segments = [
            ['label' => 'Revenue Performance', 'keywords' => ['REVENUE']],
            ['label' => 'Personnel Cost',      'keywords' => ['PERSONNEL']],
            ['label' => 'Recurrent Overhead',  'keywords' => ['OVERHEAD', 'RECURRENT']],
            ['label' => 'Capital Expenditure', 'keywords' => ['CAPITAL']],
        ];

        $summary = [];
        foreach ($segments as $segment) {
            // Fetch IDs for categories matching keywords
            $categoryIds = Category::where(function($q) use ($segment) {
                foreach ($segment['keywords'] as $key) {
                    $q->orWhere('type', 'LIKE', '%' . $key . '%');
                }
            })->pluck('id')->toArray();

            // Calculate Provisions
            $approved = (float)Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision');
            $additional = (float)Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
            $totalProvision = $approved + $additional;

            // Calculate Actuals
            $actualQuarterly = (float)$this->getMultiCategorySum($categoryIds, $qStart, $qEnd);
            $actualYTD = (float)$this->getMultiCategorySum($categoryIds, $ytdStart, $qEnd);

            // --- FIXED PERCENTAGE LOGIC ---
            // We now divide the Actual Quarterly performance by the Total Provision (Annual)
            // This matches your getExecutiveOverview logic exactly.
            $perf = $totalProvision > 0 ? ($actualQuarterly / $totalProvision) * 100 : 0;
            // ------------------------------

            $summary[] = [
                'label'      => $segment['label'],
                'approved'   => $approved,
                'additional' => $additional,
                'total_prov' => $totalProvision,
                'actual'     => $actualQuarterly,
                'balance'    => $totalProvision - $actualYTD,
                'perf'       => $perf,
            ];
        }

        $pdf = Pdf::loadView('pdf.performance.overview', [
            'summary' => $summary,
            'year'    => $year,
            'quarter' => $quarter,
            'date'    => date('d M, Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Quarterly_Summary_Q{$quarter}_{$year}.pdf");
    }
}