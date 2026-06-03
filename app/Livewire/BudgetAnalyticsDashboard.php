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
     * THE GLOBAL OPTIMIZED HOOK (Computed Property)
     * Bundles all data arrays to run under a unified cache transaction
     */
    public function getAnalyticsDataProperty()
    {
        return app(BudgetAnalyticsService::class)->getExcoDashboardState($this->filters);
    }

    /**
     * REACTIVE FILTER HOOK
     */
    public function updatedFilters()
    {
        // Flush computed runtime cache
        unset($this->analyticsData);
    }

    public function render()
    {
        // Fetch the unified payload once
        $payload = $this->analyticsData;

        return view('livewire.budget-analytics-dashboard', [
            'stats'       => $payload['stats'],
            'performance' => $payload['table'],
            'rankings'    => collect($payload['rankings']),
            'sectors'     => $payload['sectors'],
            'trends'      => $payload['trends']
        ]);
    }

    private function getPreviousQuarter($q)
    {
        return match($q) {
            '2' => '1',
            '3' => '2',
            '4' => '3',
            'all' => 'all',
            default => 'all'
        };
    }

    public function resetFilters()
    {
        $this->reset('filters');
        unset($this->analyticsData);
    }

    public function downloadPdf(ReportExportService $exportService)
    {
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
        $threshold = match($quarter) {
            '1'      => 25,
            '2'      => 50,
            '3'      => 75,
            '4', 'all' => 100,
            default  => 100
        };

        if ($percentage >= $threshold) {
            return ['label' => 'On Track', 'color' => 'emerald'];
        } elseif ($percentage >= ($threshold * 0.7)) {
            return ['label' => 'Fair', 'color' => 'amber'];
        } else {
            return ['label' => 'Low', 'color' => 'rose'];
        }
    }
}