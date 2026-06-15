<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Mda;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Auth;
use App\Services\BoBudgetPerformanceService;

class PerformanceExportController extends Controller
{
    
    public function export(Request $request, BoBudgetPerformanceService $service)
    {
        $type = $request->type ?? 'executive';
        $format = $request->format ?? 'excel';
        $quarter = $request->q ?? 'all';
        $cat = $request->cat ?? 'REVENUE'; // Defaults to REVENUE if not provided
        $year = Setting::first()->fiscal_year ?? date('Y');

        // Fetch the SAME data the user sees on the screen
        $data = match ($type) {
            'executive' => $service->getExecutiveOverview($quarter),
            'overview'  => $service->getQuarterlyReport($quarter),
            'detailed'  => $service->getDetailedReport($quarter, $cat),
            'ranking'   => $service->getRankingReport($quarter),
            default     => [],
        };

        if ($format === 'excel') {
            return $this->downloadExcel($data, $type, $quarter);
        }

        return $this->downloadPdf($data, $type, $quarter, $year);
    }

    private function getScopedDetailedData($type, $quarter, $mdaIds)
    {
        $numericQuarter = ($quarter === 'all') ? 4 : (int)$quarter;

        return Mda::whereIn('id', $mdaIds)
            ->with(['subheads' => function($q) use ($numericQuarter, $quarter) {
                $q->withSum(['releases as releases_sum_amount' => function($sq) use ($numericQuarter, $quarter) {
                    if ($quarter !== 'all') $sq->where('quarter', $numericQuarter);
                }], 'amount');
            }])->get();
    }

    // 1. Refactor downloadExcel to be a clean dispatcher
    private function downloadExcel($data, $type, $quarter)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // DEFINE THIS MISSING VARIABLE
        $displayQuarter = ($quarter === 'all') ? 'Full_Year' : "Quarter_$quarter";

        // Only call the specific builder
        if ($type === 'detailed') {
            $this->buildDetailedExcel($sheet, $data);
        } else {
            $this->buildGenericExcel($sheet, $data, $type);
        }

        $fileName = ucfirst($type) . "_Performance_{$displayQuarter}.xlsx";

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    private function buildGenericExcel($sheet, $data, $type)
    {
        // Define simple headers
        $sheet->fromArray(['Label', 'Total Provision', 'Actual', 'Performance'], NULL, 'A1');
        
        $row = 2;
        foreach ($data as $item) {
            // Convert to array to safely handle both stdClass objects and arrays
            $item = (array) $item; 
            
            // Inside buildGenericExcel method in PerformanceExportController.php
            $label = $item['label'] ?? $item['name'] ?? 'N/A';
            $prov  = $item['budget'] ?? $item['total_prov'] ?? 0; // Updated to look for 'budget' first
            $act   = $item['total'] ?? $item['actual'] ?? 0;
            $perf  = $item['perf'] ?? 0;
            
            $sheet->fromArray([$label, $prov, $act, $perf . '%'], NULL, "A$row");
            $row++;
        }
        
        // Auto-size for readability
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // 2. Ensure buildDetailedExcel handles the columns perfectly
    private function buildDetailedExcel($sheet, $data)
    {
        // Define headers
        $headers = ['Code', 'Description', 'Provision', 'Actual', 'Balance', 'Utilization (%)'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF15803D']]
        ]);

        $currentRow = 2;

        foreach ($data as $mda) {
            $prov = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
            $act = $mda->subheads->sum('releases_sum_amount');
            $bal = $prov - $act;
            $perf = $prov > 0 ? ($act / $prov) * 100 : 0;

            // MDA Header
            $sheet->setCellValue("A$currentRow", ($mda->mda_code ?? '') . ' - ' . $mda->name);
            $sheet->setCellValue("C$currentRow", $prov);
            $sheet->setCellValue("D$currentRow", $act);
            $sheet->setCellValue("E$currentRow", $bal);
            $sheet->setCellValue("F$currentRow", number_format($perf, 1) . '%');
            
            $sheet->getStyle("A$currentRow:F$currentRow")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F2937']]
            ]);
            $currentRow++;

            foreach ($mda->subheads as $sub) {
                $sProv = $sub->approved_provision + $sub->additional_provision;
                $sAct = $sub->releases_sum_amount ?? 0;
                $sBal = $sProv - $sAct;
                $sPerf = $sProv > 0 ? ($sAct / $sProv) * 100 : 0;

                $sheet->setCellValue("A$currentRow", $sub->subhead_code);
                $sheet->setCellValue("B$currentRow", $sub->description); 
                $sheet->setCellValue("C$currentRow", $sProv);
                $sheet->setCellValue("D$currentRow", $sAct);
                $sheet->setCellValue("E$currentRow", $sBal);
                $sheet->setCellValue("F$currentRow", round($sPerf, 1) . '%');
                $currentRow++;
            }
            $currentRow++; // Gap
        }

        // STYLING: Apply these AFTER the loop to cover all generated rows
        $sheet->getColumnDimension('B')->setWidth(40); // Fixes wide column
        $sheet->getStyle("C2:E" . ($currentRow - 1))
            ->getNumberFormat()
            ->setFormatCode('"₦"#,##0.00');
    }

    private function buildOverviewExcel($sheet, $data)
    {
        $headers = ['MDA Name', 'Total Provision', 'Total Actual', 'Performance %'];
        $sheet->fromArray($headers, NULL, 'A1');

        $currentRow = 2;
        foreach ($data as $mda) {
            $prov = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
            $act = $mda->subheads->sum('releases_sum_amount');
            $perf = $prov > 0 ? ($act / $prov) * 100 : 0;

            $sheet->fromArray([
                $mda->name, $prov, $act, round($perf, 1) . '%'
            ], NULL, "A$currentRow");
            $currentRow++;
        }
    }

    private function downloadPdf($data, $type, $quarter, $year)
    {
        // Convert the data collection to an array so the view can access keys like $row['total_prov']
        $summaryData = json_decode(json_encode($data), true);
        // Find your query, e.g., $summary = ...;
        //dd($summaryData); // Add this right before the return statement

        $pdf = Pdf::loadView("pdf.performance.{$type}", [
            'results'      => $summaryData, 
            'summary'      => $summaryData, // Pass the array version to your PDF view
            'quarter'      => $quarter, 
            'year'         => $year,
            'date'         => date('d M, Y'),
            'categoryName' => ucfirst($type) . ' Expenditure'
        ])->setPaper('a4', 'landscape');

        // Debug to see the structure of your data
        //dd($data->first());

        return $pdf->download("KTSG_Performance_Q{$quarter}_{$type}.pdf");
    }
}