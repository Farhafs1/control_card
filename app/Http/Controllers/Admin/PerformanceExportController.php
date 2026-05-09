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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PerformanceExportController extends Controller
{
    protected $service;

    public function __construct(BudgetPerformanceService $service)
    {
        $this->service = $service;
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', '512M'); 
        ini_set('max_execution_time', '300'); 

        // 1. Normalize Input: Ensure 'all' becomes 4 and quarter is always an integer
        $rawQuarter = $request->q ?? 1;
        $quarter = ($rawQuarter === 'all') ? 4 : (int)$rawQuarter;
        
        $type = $request->type ?? 'overview';
        $categoryKeyword = $request->cat; 
        $format = $request->format ?? 'pdf';
        $year = Setting::first()->fiscal_year ?? date('Y');

        // 2. Specialized Overview Methods
        if ($type === 'executive') {
            return $this->exportExecutiveOverview($year);
        }

        if ($type === 'overview') {
            return $this->exportQuarterlyOverview($year, $quarter);
        }

        // 3. Data Fetching for Detailed vs Ranking
        if ($type === 'detailed') {
            $data = $this->getDetailedGroupedData($year, $quarter, $categoryKeyword);
        } else {
            // --- Ranking Case: The "ERR_INVALID_RESPONSE" Fix ---
            
            // Fetch the main ranking list (Top 100 for export clarity)
            $rankingData = $this->service->getRankingReport($quarter, 123, 'desc'); 

            if ($format === 'csv') {
                // CRITICAL: CSV downloader needs a FLAT array/collection to loop through.
                // We pass ONLY the ranking list.
                $data = $rankingData; 
            } else {
                // For PDF, we keep it simple. If your template needs the 'least', 
                // you can add it here, but $data must remain loopable for the view.
                $data = $rankingData;
            }
        }

        // 4. Dispatch to Format Handler
        if ($format === 'csv') {
            return $this->downloadCsv($data, $type, $rawQuarter);
        }


        // 5. Format Dispatcher: Excel & Detailed CSV
        if ($format === 'excel' || ($format === 'csv' && $type === 'detailed')) {
            // We only send 'detailed' data to the Excel generator 
            // because that's what the downloadExcel method is built to loop through.
            if ($type === 'detailed') {
                return $this->downloadExcel($data, $type, $rawQuarter);
            }

            // If they asked for Excel on an Overview/Ranking page, 
            // fallback to CSV so the app doesn't crash.
            return $this->downloadCsv($data, $type, $rawQuarter);
        }

        // 6. PDF Generation
        $pdf = Pdf::loadView("pdf.performance.{$type}", [
            'results'      => $data, 
            'quarter'      => $rawQuarter, // Use original 'all' or '1' for the label
            'date'         => date('d M, Y'),
            'year'         => $year,
            'categoryName' => $categoryKeyword 
        ])->setPaper('a4', 'landscape');

        return $pdf->download("KTSG_Performance_Q{$rawQuarter}_{$type}.pdf");
    }
    /**
     * Internal Logic to match the Livewire Component's detailed grouping
     */
    private function getDetailedGroupedData($year, $quarter, $masterType)
    {
        // If $quarter is 4 (All), we want everything <= 4. 
        // If it's 1, 2, or 3, we want ONLY that specific integer.
        $isAllQuarters = ($quarter == 4); 

        $keywords = ($masterType === 'OVERHEAD') ? ['OVERHEAD', 'RECURRENT'] : [$masterType];
        
        $categoryIds = Category::where(function($q) use ($keywords) {
            foreach ($keywords as $key) {
                $q->orWhere('type', 'LIKE', '%' . $key . '%');
            }
        })->pluck('id');

        return Mda::with(['subheads' => function($q) use ($categoryIds, $quarter, $isAllQuarters) {
            $q->whereIn('category_id', $categoryIds)
            ->withSum(['releases as releases_sum_amount' => function($sq) use ($quarter, $isAllQuarters) {
                if ($isAllQuarters) {
                    // For "All", sum up everything from the start of the year
                    $sq->where('quarter', '<=', $quarter); 
                } else {
                    // For a specific quarter, get ONLY that quarter's releases
                    $sq->where('quarter', '=', $quarter); 
                }
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
                $summary[] = ['label' => $segment['label'], 'total_prov' => 0, 'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'perf' => 0];
                continue;
            }

            $budget = Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision') + 
                    Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
            
            // REFACTORED: We now pass simple integers. 
            // Note: This requires the updated getMultiCategorySum($ids, $quarter) we discussed earlier.
            $q1 = $this->getMultiCategorySum($categoryIds, 1);
            $q2 = $this->getMultiCategorySum($categoryIds, 2);
            $q3 = $this->getMultiCategorySum($categoryIds, 3);
            $q4 = $this->getMultiCategorySum($categoryIds, 4);

            $totalActual = $q1 + $q2 + $q3 + $q4;

            $summary[] = [
                'label'      => $segment['label'],
                'total_prov' => $budget,
                'q1'         => $q1, 
                'q2'         => $q2, 
                'q3'         => $q3, 
                'q4'         => $q4,
                'total'      => $totalActual,
                'perf'       => $budget > 0 ? ($totalActual / $budget) * 100 : 0
            ];
        }

        $pdf = Pdf::loadView('pdf.performance.executive', [
            'summary' => $summary,
            'year'    => $year,
            'date'    => date('d M, Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Executive_Budget_Overview_{$year}.pdf");
    }
    
    private function getMultiCategorySum($categoryIds, $quarter)
    {
        return Release::whereHas('subhead', function($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })
            ->where('quarter', $quarter) // Precise integer match
            ->sum('amount');
    }

    private function downloadCsv($data, $type, $quarter)
    {
        // Ensure quarter isn't the string 'all' for the filename
        $displayQuarter = ($quarter === 'all') ? 'Full_Year' : "Q{$quarter}";
        $filename = "Performance_{$displayQuarter}_{$type}.csv";
        
        $callback = function() use ($data, $type) {
            $handle = fopen('php://output', 'w');
            
            // --- TYPE 1: DETAILED ---
            if ($type === 'detailed') {
                fputcsv($handle, ['MDA', 'Subhead', 'Approved Provision', 'Additional Provision', 'Total Budget', 'Actual Release', 'Performance %']);
                foreach ($data as $mda) {
                    foreach ($mda->subheads as $sub) {
                        $total = $sub->approved_provision + $sub->additional_provision;
                        $actual = $sub->releases_sum_amount ?? 0;
                        // Note: If you switched to cumulative (<=), compare against full total
                        $perf = $total > 0 ? ($actual / $total) * 100 : 0;
                        fputcsv($handle, [$mda->name, $sub->name, $sub->approved_provision, $sub->additional_provision, $total, $actual, round($perf, 2)]);
                    }
                }
            } 
            // --- TYPE 2 & 3: OVERVIEW/EXECUTIVE ---
            elseif ($type === 'overview' || $type === 'executive') {
                fputcsv($handle, ['Category/Segment', 'Total Provision', 'Actual Release', 'Performance %']);
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['label'] ?? 'N/A', 
                        $row['total_prov'] ?? 0, 
                        $type === 'executive' ? ($row['total'] ?? 0) : ($row['actual'] ?? 0),
                        round($row['perf'] ?? 0, 2)
                    ]);
                }
            }
            // --- TYPE 4: RANKING (The fix is here) ---
            else {
                fputcsv($handle, ['Rank', 'Code', 'MDA Name', 'Revenue', 'Personnel', 'Overhead', 'Capital', 'Total Spend']);
                foreach ($data as $index => $mda) {
                    // We use data_get() to safely handle both Arrays and Objects
                    fputcsv($handle, [
                        $index + 1,
                        data_get($mda, 'mda_code', 'N/A'),
                        data_get($mda, 'mda_name', data_get($mda, 'name', 'N/A')),
                        data_get($mda, 'revenue', 0),
                        data_get($mda, 'personnel', 0),
                        data_get($mda, 'overhead', 0),
                        data_get($mda, 'capital', 0),
                        data_get($mda, 'total_spend', data_get($mda, 'total_releases', 0))
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function exportQuarterlyOverview($year, $quarter)
    {
        // REFACTORED: Removed the $periods array and date string variables
        // to utilize the new 'quarter' column directly.

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

            // Calculate Provisions (Logic preserved exactly)
            $approved = (float)Subhead::whereIn('category_id', $categoryIds)->sum('approved_provision');
            $additional = (float)Subhead::whereIn('category_id', $categoryIds)->sum('additional_provision');
            $totalProvision = $approved + $additional;

            // REFACTORED: Use the quarter integer for current quarterly actuals
            $actualQuarterly = (float)$this->getMultiCategorySum($categoryIds, $quarter);

            // REFACTORED: Calculate YTD by summing all quarters up to the current one
            $actualYTD = (float)Release::whereHas('subhead', function($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })
                ->where('quarter', '<=', $quarter) // Sums Q1, Q2... up to $quarter
                ->sum('amount');

            // --- FIXED PERCENTAGE LOGIC (PRESERVED) ---
            $perf = $totalProvision > 0 ? ($actualQuarterly / $totalProvision) * 100 : 0;
            // ------------------------------------------

            $summary[] = [
                'label'      => $segment['label'],
                'approved'   => $approved,
                'additional' => $additional,
                'total_prov' => $totalProvision,
                'actual'     => $actualQuarterly,
                'balance'    => $totalProvision - $actualYTD, // Logic preserved
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

    private function downloadExcel($data, $type, $quarter)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $displayQuarter = ($quarter === 'all') ? 'Full Year' : "Quarter $quarter";
        
        // 1. Setup Column Headers (matching your UI table)
        $headers = ['Code', 'Description (Subhead)', 'Approved Provision', 'Actual Qtr (Total)', 'Utilization (%)'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF15803D'] // bg-green-700
            ]
        ]);

        $currentRow = 2;

        foreach ($data as $mda) {
            // --- 2. MIRROR UI LOGIC: MDA-level totals ---
            $mdaTotalProv = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
            $mdaTotalActual = $mda->subheads->sum('releases_sum_amount');
            $mdaPerf = $mdaTotalProv > 0 ? ($mdaTotalActual / $mdaTotalProv) * 100 : 0;

            // --- 3. MDA DEMARCATION ROW ---
            $mdaTitle = ($mda->mda_code ?? '') . ' - ' . $mda->name;
            $sheet->setCellValue("A$currentRow", $mdaTitle . " (Performance: " . number_format($mdaPerf, 1) . "%)");
            
            // Style: Dark Gray Background & White Bold Text (bg-gray-800)
            $sheet->mergeCells("A$currentRow:E$currentRow");
            $sheet->getStyle("A$currentRow:E$currentRow")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'] // bg-gray-800
                ]
            ]);
            
            $currentRow++;

            // --- 4. INDIVIDUAL SUBHEAD ROWS ---
            foreach ($mda->subheads as $subhead) {
                $totalProvision = $subhead->approved_provision + $subhead->additional_provision;
                $actual = $subhead->releases_sum_amount ?? 0;
                $perf = $totalProvision > 0 ? ($actual / $totalProvision) * 100 : 0;

                $sheet->setCellValue("A$currentRow", $subhead->subhead_code);
                $sheet->setCellValue("B$currentRow", $subhead->description); // Matches UI 'description'
                $sheet->setCellValue("C$currentRow", $totalProvision);
                $sheet->setCellValue("D$currentRow", $actual);
                $sheet->setCellValue("E$currentRow", round($perf, 1) . '%');
                
                // Numerical Formatting (₦)
                $sheet->getStyle("C$currentRow:D$currentRow")
                    ->getNumberFormat()
                    ->setFormatCode('"₦"#,##0.00');

                // Alignment matching UI (text-right)
                $sheet->getStyle("C$currentRow:E$currentRow")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $currentRow++;
            }
            
            // Add a small spacer row
            $currentRow++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Detailed_Performance_{$displayQuarter}.xlsx";

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }
}