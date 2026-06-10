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

class PerformanceExportController extends Controller
{
    
    public function export(Request $request)
    {
        $type = $request->type ?? 'detailed'; // e.g., 'detailed', 'overview'
        $format = $request->format ?? 'pdf';
        $rawQuarter = $request->q ?? 'all';
        $year = Setting::first()->fiscal_year ?? date('Y');

        // Fetch the data dynamically based on the type
        $mdaIds = Auth::user()->mdas()->pluck('id');
        $data = $this->getScopedDetailedData($type, $rawQuarter, $mdaIds);

        if ($format === 'excel') {
            return $this->downloadExcel($data, $type, $rawQuarter);
        }

        return $this->downloadPdf($data, $type, $rawQuarter, $year);
    }

    private function getScopedDetailedData($type, $quarter, $mdaIds)
    {
        $numericQuarter = ($quarter === 'all') ? 4 : (int)$quarter;

        // Here we ensure we fetch correctly based on the 'type'
        return Mda::whereIn('id', $mdaIds)
            ->with(['subheads' => function($q) use ($numericQuarter, $quarter) {
                $q->withSum(['releases as releases_sum_amount' => function($sq) use ($numericQuarter, $quarter) {
                    if ($quarter !== 'all') $sq->where('quarter', $numericQuarter);
                }], 'amount');
            }])->get();
    }

    
    private function downloadExcel($data, $quarter)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Updated Headers with Balance
        $headers = ['Code', 'Description', 'Provision', 'Actual', 'Balance', 'Utilization (%)'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $currentRow = 2;
        foreach ($data as $mda) {
            foreach ($mda->subheads as $sub) {
                $prov = $sub->approved_provision + $sub->additional_provision;
                $act = $sub->releases_sum_amount ?? 0;
                $bal = $prov - $act;
                $perf = $prov > 0 ? ($act / $prov) * 100 : 0;

                $sheet->fromArray([
                    $sub->subhead_code, $sub->description, $prov, $act, $bal, round($perf, 1) . '%'
                ], NULL, "A$currentRow");
                $currentRow++;
            }
        }
        
        return response()->streamDownload(fn() => (new Xlsx($spreadsheet))->save('php://output'), "Report_Q{$quarter}.xlsx");
    }


    private function downloadPdf($data, $type, $quarter, $year)
    {
        $pdf = Pdf::loadView("pdf.performance.{$type}", [
            'results'      => $data, 
            'quarter'      => $quarter, 
            'year'         => $year,
            'date'         => date('d M, Y'),
            // Add this line to fix the error:
            'categoryName' => 'All Categories' 
        ])->setPaper('a4', 'landscape');

        return $pdf->download("KTSG_Performance_Q{$quarter}_{$type}.pdf");
    }
}