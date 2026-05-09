<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Flexible CSV Export that detects the report type based on data keys
     */
    public function exportToCsv($data, $filename = 'budget_report.csv'): StreamedResponse
    {
        // Convert to collection if it's a plain array
        $data = collect($data);

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            if ($data->isEmpty()) {
                fputcsv($file, ['No data available for this selection']);
                fclose($file);
                return;
            }

            // 1. DETERMINE DATA SHAPE (Based on the first row)
            $firstRow = (array) $data->first();

            // 2. SET DYNAMIC HEADERS
            if (isset($firstRow['category_name'])) {
                // Shape: Executive Overview
                fputcsv($file, ['Category Type', 'Total Provision (₦)', 'Total Actual (₦)', 'Performance (%)']);
                foreach ($data as $row) {
                    fputcsv($file, [$row->category_name, $row->total_provision, $row->total_actual, number_format($row->performance_pct, 2) . '%']);
                }
            } 
            elseif (isset($firstRow['mda_name']) && isset($firstRow['total_spend'])) {
                // Shape: Ranking Report
                fputcsv($file, ['MDA Name', 'MDA Code', 'Revenue', 'Personnel', 'Overhead', 'Capital', 'Total Spend (₦)']);
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['mda_name'], 
                        $row['mda_code'], 
                        $row['revenue'], 
                        $row['personnel'], 
                        $row['overhead'], 
                        $row['capital'], 
                        $row['total_spend']
                    ]);
                }
            }
            else {
                // Shape: Detailed MDA/Subhead Report (Legacy/Standard)
                fputcsv($file, ['Code', 'Description', 'Budget (₦)', 'Actual (₦)', 'Performance (%)']);
                foreach ($data as $row) {
                    // Use array access or object access safely
                    $row = (object)$row;
                    fputcsv($file, [
                        $row->mda_code ?? $row->code ?? 'N/A',
                        $row->name ?? $row->description ?? 'N/A',
                        $row->total_provision ?? $row->budget ?? 0,
                        $row->total_actual ?? $row->actual ?? 0,
                        number_format($row->performance_pct ?? $row->percentage ?? 0, 2) . '%'
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Updated PDF Export
     */
    public function exportToPdf($data, $stats, $settings, $quarter_label)
    {
        // Check if data is for the Ranking report to switch orientation
        $isRanking = collect($data)->every(fn($item) => isset($item['total_spend']));
        $orientation = $isRanking ? 'landscape' : 'portrait';

        $pdf = \PDF::loadView('reports.budget-performance-pdf', [
            'performance' => $data,
            'stats' => $stats,
            'settings' => $settings,
            'quarter_label' => $quarter_label
        ]);

        return $pdf->setPaper('a4', $orientation)->setWarnings(false);
    }
}