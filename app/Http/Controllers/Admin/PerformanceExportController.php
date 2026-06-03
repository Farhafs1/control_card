<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BudgetPerformanceService;
use App\Models\Subhead;
use App\Models\Release;
use App\Models\Setting;
use App\Models\Mda;
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

    /**
     * Primary Export Entry Routing Matrix.
     */
    public function export(Request $request)
    {
        ini_set('memory_limit', '512M'); 
        ini_set('max_execution_time', '300'); 

        // 1. Capture Raw Input Variables to prevent premature truncation
        $rawQuarter = $request->q ?? 'all'; 
        $quarter = ($rawQuarter === 'all') ? 4 : (int)$rawQuarter;
        
        $type = $request->type ?? 'overview';
        $categoryKeyword = $request->cat; 
        $format = $request->format ?? 'pdf';
        $year = Setting::first()->fiscal_year ?? date('Y');

        // 2. High-Performance Overview Dispatch Checkers
        if ($type === 'executive') {
            // FIXED: Now safely passes BOTH arguments down to eliminate the ArgumentCountError
            return $this->exportExecutiveOverview($year, $rawQuarter);
        }

        if ($type === 'overview') {
            return $this->exportQuarterlyOverview($year, $quarter);
        }

        // 3. Data Fetching for Detailed and Ranking Reports
        if ($type === 'detailed') {
            $data = $this->getDetailedGroupedData($year, $rawQuarter, $categoryKeyword);
        } else {
            $rankingData = $this->service->getRankingReport($rawQuarter, 123, 'desc'); 
            $data = $rankingData;
        }

        // 4. Dispatch Format Stream Output Handlers
        if ($format === 'csv') {
            return $this->downloadCsv($data, $type, $rawQuarter);
        }

        if ($format === 'excel' || ($format === 'csv' && $type === 'detailed')) {
            if ($type === 'detailed') {
                return $this->downloadExcel($data, $type, $rawQuarter);
            }
            return $this->downloadCsv($data, $type, $rawQuarter);
        }

        // 5. Standard PDF Document Compiler Pipeline
        $pdf = Pdf::loadView("pdf.performance.{$type}", [
            'results'      => $data, 
            'quarter'      => $rawQuarter, 
            'date'         => date('d M, Y'),
            'year'         => $year,
            'categoryName' => $categoryKeyword 
        ])->setPaper('a4', 'landscape');

        return $pdf->download("KTSG_Performance_Q{$rawQuarter}_{$type}.pdf");
    }

    /**
     * Resolves Master Categories using deterministic accounting code architecture.
     */
    private function getSubheadIdsForExport($masterType)
    {
        $codeColumn = 'subhead_code'; // Matches your local SQLite & live schema configuration
        $query = Subhead::query();

        if ($masterType === 'CAPITAL') {
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
     * Data layer constructor to mirror the detailed interactive dashboard template engine.
     */
    private function getDetailedGroupedData($year, $quarter, $masterType)
    {
        $subheadIds = $this->getSubheadIdsForExport($masterType);
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
     * Compiles Executive Multi-Quarter Overview with optimized raw aggregation commands.
     */
    private function exportExecutiveOverview($year, $rawQuarter)
    {
        $segments = [
            ['label' => 'Revenue Performance', 'type' => 'REVENUE'],
            ['label' => 'Personnel Cost',       'type' => 'PERSONNEL'],
            ['label' => 'Recurrent Overhead',   'type' => 'OVERHEAD'],
            ['label' => 'Capital Expenditure', 'type' => 'CAPITAL'],
        ];

        $dataset = []; 

        foreach ($segments as $segment) {
            $subheadIds = $this->getSubheadIdsForExport($segment['type']);

            if (empty($subheadIds)) {
                $dataset[] = [
                    'label'      => $segment['label'], 
                    'total_prov' => 0, 
                    'q1'         => 0, 
                    'q2'         => 0, 
                    'q3'         => 0, 
                    'q4'         => 0, 
                    'total'      => 0, 
                    'perf'       => 0
                ];
                continue;
            }

            $approved = Subhead::whereIn('id', $subheadIds)->sum('approved_provision');
            $additional = Subhead::whereIn('id', $subheadIds)->sum('additional_provision');
            $budget = $approved + $additional;
            
            $quarterSums = Release::whereIn('subhead_id', $subheadIds)
                ->selectRaw("
                    SUM(CASE WHEN quarter = 1 THEN amount ELSE 0 END) as q1,
                    SUM(CASE WHEN quarter = 2 THEN amount ELSE 0 END) as q2,
                    SUM(CASE WHEN quarter = 3 THEN amount ELSE 0 END) as q3,
                    SUM(CASE WHEN quarter = 4 THEN amount ELSE 0 END) as q4
                ")
                ->first();

            $q1 = (float)($quarterSums->q1 ?? 0);
            $q2 = (float)($quarterSums->q2 ?? 0);
            $q3 = (float)($quarterSums->q3 ?? 0);
            $q4 = (float)($quarterSums->q4 ?? 0);
            $totalActual = $q1 + $q2 + $q3 + $q4;

            $dataset[] = [
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

        // FIXED: Providing both aliases ensures compatibility across different layouts
        $pdf = Pdf::loadView('pdf.performance.executive', [
            'results' => $dataset, // Satisfies interactive widget loops
            'summary' => $dataset, // Fixes line 101 of your print export layout engine
            'year'    => $year,
            'quarter' => $rawQuarter, 
            'date'    => date('d M, Y')
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Executive_Budget_Overview_{$year}.pdf");
    }
    
    /**
     * Compiles Target single quarter perspective report parameters.
     */
    private function exportQuarterlyOverview($year, $quarter)
    {
        $segments = [
            ['label' => 'Revenue Performance', 'type' => 'REVENUE'],
            ['label' => 'Personnel Cost',       'type' => 'PERSONNEL'],
            ['label' => 'Recurrent Overhead',   'type' => 'OVERHEAD'],
            ['label' => 'Capital Expenditure', 'type' => 'CAPITAL'],
        ];

        $summary = [];
        foreach ($segments as $segment) {
            $subheadIds = $this->getSubheadIdsForExport($segment['type']);

            $approved = (float)Subhead::whereIn('id', $subheadIds)->sum('approved_provision');
            $additional = (float)Subhead::whereIn('id', $subheadIds)->sum('additional_provision');
            $totalProvision = $approved + $additional;

            $actualQuarterly = (float)$this->getMultiCategorySum($subheadIds, $quarter);

            // Fetch cumulative year-to-date allocations up to the requested quarter
            $actualYTD = (float)Release::whereIn('subhead_id', $subheadIds)
                ->where('quarter', '<=', $quarter)
                ->sum('amount');

            $perf = $totalProvision > 0 ? ($actualQuarterly / $totalProvision) * 100 : 0;

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
    
    /**
     * Fetches simple flat numeric aggregates.
     */
    private function getMultiCategorySum($subheadIds, $quarter)
    {
        if (empty($subheadIds)) {
            return 0;
        }

        return Release::whereIn('subhead_id', $subheadIds)
            ->where('quarter', $quarter)
            ->sum('amount');
    }

    /**
     * CSV Builder engine streaming payload outputs.
     */
    private function downloadCsv($data, $type, $quarter)
    {
        $displayQuarter = ($quarter === 'all') ? 'Full_Year' : "Q{$quarter}";
        $filename = "Performance_{$displayQuarter}_{$type}.csv";
        
        $callback = function() use ($data, $type) {
            $handle = fopen('php://output', 'w');
            
            if ($type === 'detailed') {
                fputcsv($handle, ['MDA', 'Subhead', 'Approved Provision', 'Additional Provision', 'Total Budget', 'Actual Release', 'Performance %']);
                foreach ($data as $mda) {
                    foreach ($mda->subheads as $sub) {
                        $total = $sub->approved_provision + $sub->additional_provision;
                        $actual = $sub->releases_sum_amount ?? 0;
                        $perf = $total > 0 ? ($actual / $total) * 100 : 0;
                        fputcsv($handle, [$mda->name, $sub->subhead_code . ' - ' . $sub->description, $sub->approved_provision, $sub->additional_provision, $total, $actual, round($perf, 2)]);
                    }
                }
            } 
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
            else {
                fputcsv($handle, ['Rank', 'Code', 'MDA Name', 'Revenue', 'Personnel', 'Overhead', 'Capital', 'Total Spend']);
                foreach ($data as $index => $mda) {
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

    /**
     * Compiles detailed data elements into styled structural Microsoft Excel binary outputs.
     */
    private function downloadExcel($data, $type, $quarter)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $displayQuarter = ($quarter === 'all') ? 'Full Year' : "Quarter $quarter";
        
        $headers = ['Code', 'Description (Subhead)', 'Approved Provision', 'Actual Qtr (Total)', 'Utilization (%)'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF15803D'] 
            ]
        ]);

        $currentRow = 2;

        foreach ($data as $mda) {
            $mdaTotalProv = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
            $mdaTotalActual = $mda->subheads->sum('releases_sum_amount');
            $mdaPerf = $mdaTotalProv > 0 ? ($mdaTotalActual / $mdaTotalProv) * 100 : 0;

            $mdaTitle = ($mda->mda_code ?? '') . ' - ' . $mda->name;
            $sheet->setCellValue("A$currentRow", $mdaTitle . " (Performance: " . number_format($mdaPerf, 1) . "%)");
            
            $sheet->mergeCells("A$currentRow:E$currentRow");
            $sheet->getStyle("A$currentRow:E$currentRow")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'] 
                ]
            ]);
            
            $currentRow++;

            foreach ($mda->subheads as $subhead) {
                $totalProvision = $subhead->approved_provision + $subhead->additional_provision;
                $actual = $subhead->releases_sum_amount ?? 0;
                $perf = $totalProvision > 0 ? ($actual / $totalProvision) * 100 : 0;

                $sheet->setCellValue("A$currentRow", $subhead->subhead_code);
                $sheet->setCellValue("B$currentRow", $subhead->description); 
                $sheet->setCellValue("C$currentRow", $totalProvision);
                $sheet->setCellValue("D$currentRow", $actual);
                $sheet->setCellValue("E$currentRow", round($perf, 1) . '%');
                
                $sheet->getStyle("C$currentRow:D$currentRow")
                    ->getNumberFormat()
                    ->setFormatCode('"₦"#,##0.00');

                $sheet->getStyle("C$currentRow:E$currentRow")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $currentRow++;
            }
            
            $currentRow++;
        }

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