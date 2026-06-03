<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BudgetAnalyticsService;
use App\Services\ReportExportService;

class BudgetAnalyticsDashboard extends Component
{
    // Phase 1: Grouped Filters
    public $filters = [
        'quarter' => 'all',
        'type' => null,
        'category' => null,
        'groupBy' => 'category'
    ];

    /**
     * THE GLOBAL HOOK (Computed Property)
     * This ensures Stats, Table, and Rankings update simultaneously 
     * whenever $this->filters changes.
     */
    public function getAnalyticsDataProperty()
    {
        // This calls the service method that bundles stats, table, and rankings
        return app(BudgetAnalyticsService::class)->getExcoDashboardState($this->filters);
    }

    /**
     * REACTIVE FILTER HOOK
     * This magic method runs whenever ANY property in $filters changes.
     */
    public function updatedFilters()
    {
        /**
         * CRITICAL: Clear the computed property cache.
         * This forces 'getAnalyticsDataProperty' to re-fetch fresh data 
         * from the service using the NEW filter values.
         */
        unset($this->analyticsData);
    }

    public function render(BudgetAnalyticsService $service)
    {
        // 1. Fetch data from the Computed Property (now guaranteed to be fresh)
        $data = $this->analyticsData;

        // 2. Fetch Module-specific data
        $sectorData = $service->getSectoralDeepDive($this->filters['quarter']);
        
        $previousQuarter = $this->getPreviousQuarter($this->filters['quarter']);
        $trends = $service->getQuarterlyTrend($this->filters['quarter'], $previousQuarter);

        return view('livewire.budget-analytics-dashboard', [
            'stats'       => $data['stats'],
            'performance' => $data['table'],
            'rankings'    => collect($data['rankings']),
            'sectors'     => $sectorData,
            'trends'      => $trends
        ]);
    }

    private function getPreviousQuarter($q)
    {
        return match($q) {
            '2' => '1',
            '3' => '2',
            '4' => '3',
            default => 'all'
        };
    }

    public function resetFilters()
    {
        $this->reset('filters');
        unset($this->analyticsData); // Also clear cache on reset
    }

    public function downloadPdf(ReportExportService $exportService)
    {
        // Uses the current fresh state for the export
        $data = $this->analyticsData['table'];
        $stats = $this->analyticsData['stats'];
        $settings = \App\Models\Setting::current();
        
        $quarterLabel = $this->filters['quarter'] === 'all' ? 'Full Year' : "Quarter {$this->filters['quarter']}";

        return response()->streamDownload(function () use ($exportService, $data, $stats, $settings, $quarterLabel) {
            echo $exportService->exportToPdf($data, $stats, $settings, $quarterLabel)->output();
        }, "Budget_Report_{$quarterLabel}.pdf");
    }
    public function getPerformanceStatus($percentage)
    {
        $quarter = $this->filters['quarter'] ?? 'all';

        // Define what "100% success" looks like for the selected period
        $threshold = match($quarter) {
            '1'     => 25,  // End of March/April should be ~25%
            '2'     => 50,
            '3'     => 75,
            '4', 'all' => 100,
            default => 100
        };

        // Calculate how far we are from the period's goal
        $performanceRatio = $percentage; 

        if ($performanceRatio >= $threshold) {
            return ['label' => 'On Track', 'color' => 'emerald'];
        } elseif ($performanceRatio >= ($threshold * 0.7)) {
            return ['label' => 'Fair', 'color' => 'amber'];
        } else {
            return ['label' => 'Low', 'color' => 'rose'];
        }
    }
}