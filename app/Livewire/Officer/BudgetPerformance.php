<?php 

namespace App\Livewire\Officer;

use App\Models\Setting;
use App\Services\BoBudgetPerformanceService;
use Livewire\{Component, Attributes\Computed};

class BudgetPerformance extends Component
{
    public $categoryId = null; 
    public $reportType = 'executive'; 
    public $quarter = 'all';
    
    // Add this public property so the view can access $results
    public $results = []; 

    public function updatedReportType()
    {
        // 1. Reset logic
        if ($this->reportType === 'executive') {
            $this->quarter = 'all';
        }
        
        if ($this->reportType !== 'detailed') {
            $this->categoryId = null;
        }

        // 2. State management: If you need to trigger a re-render 
        // with the new report type, Livewire handles this automatically.
    }

    public function render(BoBudgetPerformanceService $service)
    {
        $settings = Setting::first();
        $year = $settings->fiscal_year ?? date('Y');

        $results = match ($this->reportType) {
            'executive' => $service->getExecutiveOverview('all'),
            'overview'  => $service->getQuarterlyReport($this->quarter),
            'detailed'  => $service->getDetailedReport($this->quarter, $this->categoryId ?: 'REVENUE'),
            'ranking'   => $service->getRankingReport($this->quarter),
            default     => [],
        };

        return view('livewire.officer.budget-performance', [
            'dataset' => $results, // Use 'dataset' so all partials continue to work
            'year'    => $year
        ])->layout('layouts.app');
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

    public function export()
    {
        return redirect()->route('officer.export', [
            'type'   => $this->reportType,
            'q'      => $this->quarter,
            'cat'    => $this->categoryId,
            'format' => 'excel'
        ]);
    }

    public function exportPdf()
    {
        return redirect()->route('officer.export', [
            'type'   => $this->reportType,
            'q'      => $this->quarter,
            'cat'    => $this->categoryId,
            'format' => 'pdf'
        ]);
    }
}