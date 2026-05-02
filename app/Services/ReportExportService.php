<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Generate a CSV Export of the current filtered analytics
     */
    public function exportToCsv(Collection $data, $filename = 'budget_report.csv'): StreamedResponse
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Code', 
                'Description', 
                'MDA', 
                'Category', 
                'Type', 
                'Budget (₦)', 
                'Actual (₦)', 
                'Variance (₦)', 
                'Performance (%)', 
                'Status'
            ]);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->code,
                    $row->description,
                    $row->mda_name,
                    str_replace('_', ' ', $row->category),
                    $row->type,
                    $row->budget,
                    $row->actual,
                    $row->variance,
                    $row->percentage . '%',
                    $row->status_label
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Inside App\Services\ReportExportService.php

    public function exportToPdf($data, $stats, $settings, $quarter_label)
    {
        $pdf = \PDF::loadView('reports.budget-performance-pdf', [
            'performance' => $data,
            'stats' => $stats,
            'settings' => $settings,
            'quarter_label' => $quarter_label
        ]);

        // Set paper to A4 and orientation to Landscape if the table is wide
        return $pdf->setPaper('a4', 'portrait')->setWarnings(false);
    }
}