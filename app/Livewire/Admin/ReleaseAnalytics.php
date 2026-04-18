<?php

namespace App\Livewire\Admin;

use App\Models\Release;
use App\Models\Category;
use App\Models\MDA;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Gemini\Laravel\Facades\Gemini;

class ReleaseAnalytics extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $minAmount = '';
    public $categoryId = '';
    public $startDate = '';
    public $endDate = '';

    // AI & View Properties
    public $aiAnalysis = '';
    public $isAnalyzing = false;
    public $showInsight = false; // Controls the view toggle

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'minAmount' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingCategoryId() { $this->resetPage(); }
    public function updatingMinAmount() { $this->resetPage(); }

    /**
     * Toggles back to the data table view
     */
    public function closeInsight()
    {
        $this->showInsight = false;
    }

    /**
     * AI Analysis Logic
     */

    public function generateAIReport()
    {
        $this->showInsight = true;
        $this->isAnalyzing = true;
        $this->aiAnalysis = ''; 
        
        // Ensure the server doesn't timeout during the AI's "thinking" process
        set_time_limit(150); 

        try {
            $summary = $this->prepareDataForAI();

            $prompt = "You are the Chief Financial Auditor for Katsina State. 
            Analyze this executive expenditure summary:
            
            $summary
            
            Provide a professional 5-paragraph executive brief with specific headings:
            1. SPENDING TRENDS: What sectors are dominating?
            2. RISK & ANOMALIES: Are there unusual high-value concentrations?
            3. STRATEGIC RECOMMENDATION: One actionable directive for the Governor.
            
            Keep the tone authoritative and professional for a high-level government briefing.";

            // Use the exact logic from your working /ai-test route
            $client = app(\Gemini\Client::class);
            $result = $client->generativeModel('gemini-2.5-flash')
                            ->generateContent($prompt);

            $this->aiAnalysis = $result->text();
            
        } catch (\Exception $e) {
            // Log the error for your own debugging while showing a clean message to the user
            \Log::error("Gemini AI Error: " . $e->getMessage());
            $this->aiAnalysis = "The AI Financial Consultant is currently offline. Please try again in a moment.";
        }

        $this->isAnalyzing = false;
    }
    // public function generateAIReport()
    // {
    //     $this->showInsight = true; // Switch to the insight view immediately
    //     $this->isAnalyzing = true;
    //     $this->aiAnalysis = ''; 
        
    //     try {
    //         $summary = $this->prepareDataForAI();

    //         $prompt = "You are the Chief Financial Auditor for Katsina State. 
    //         Analyze this executive expenditure summary:
            
    //         $summary
            
    //         Based on this data, provide a professional 3-paragraph executive brief:
    //         1. SPENDING TRENDS: Identify which sectors or MDAs are dominating the budget.
    //         2. RISK & ANOMALIES: Note any high-value concentrations or unusual distributions.
    //         3. STRATEGIC RECOMMENDATION: Provide one actionable directive for the Governor to optimize state liquidity.
            
    //         Format the output with clear headings. Use an authoritative, executive tone.";

    //         $result = Gemini::gemini()->generateContent($prompt);
    //         $this->aiAnalysis = $result->text();
            
    //     } catch (\Exception $e) {
    //         $this->aiAnalysis = "The AI Financial Consultant is currently offline. Please try again in a moment. Error: " . $e->getMessage();
    //     }

    //     $this->isAnalyzing = false;
    // }

    /**
     * Enhanced data preparation for a more accurate AI response
     */
    private function prepareDataForAI()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);

        $total = $baseQuery->sum('amount');
        $count = $baseQuery->count();
        
        // Get Top 3 MDAs
        $topMdas = (clone $baseQuery)->join('mdas', 'releases.mda_id', '=', 'mdas.id')
                    ->select('mdas.name', DB::raw('SUM(amount) as total'))
                    ->groupBy('mdas.name')->orderBy('total', 'desc')->limit(3)->get();
        
        $mdaString = $topMdas->map(fn($m) => "{$m->name} (₦" . number_format($m->total, 2) . ")")->implode(', ');

        // Get Top Category
        $topCat = (clone $baseQuery)->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
                    ->join('categories', 'subheads.category_id', '=', 'categories.id')
                    ->select('categories.type', DB::raw('SUM(releases.amount) as total'))
                    ->groupBy('categories.type')->orderBy('total', 'desc')->first();

        return "DATA SUMMARY:
        - Total Expenditure: ₦" . number_format($total, 2) . "
        - Volume: $count total releases
        - Leading Sector: " . ($topCat->type ?? 'N/A') . "
        - Top 3 Spending MDAs: $mdaString";
    }

    public function exportReport($format = 'pdf')
    {
        // Determine which route name to use based on the format
        $routeName = ($format === 'ppt') 
            ? 'admin.expenditure.ppt' 
            : 'admin.expenditure.export';

        return redirect()->route($routeName, [
            'search' => $this->search,
            'minAmount' => $this->minAmount,
            'categoryId' => $this->categoryId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'format' => $format,
            'ai_text' => $this->aiAnalysis 
        ]);
    }

    public function render()
    {
        $baseQuery = Release::query()->select('releases.*');
        $this->applyFilters($baseQuery);

        // 1. Stats Calculation
        $totalReleased = (float) (clone $baseQuery)->sum('amount');
        $stats = [
            'total_value' => $totalReleased,
            'count'       => (clone $baseQuery)->count(),
            'avg_release' => (float) (clone $baseQuery)->avg('amount') ?? 0,
            'max_release' => (float) (clone $baseQuery)->max('amount') ?? 0,
        ];

        // 2. Budget Performance (Burn Rate)
        // Replace 50000000000 with your actual annual approved budget total
        $approvedBudget = 50000000000; 
        $burnRate = $approvedBudget > 0 ? round(($totalReleased / $approvedBudget) * 100, 1) : 0;

        // 3. Chart Data: Sectoral Breakdown (Personnel vs Overhead vs Capital)
        $sectorChartData = (clone $baseQuery)
            ->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
            ->join('categories', 'subheads.category_id', '=', 'categories.id')
            ->select('categories.type as label', DB::raw('CAST(SUM(releases.amount) AS DECIMAL(20,2)) as total'))
            ->groupBy('categories.type')->get()
            ->map(fn($item) => ['label' => $item->label, 'total' => (float) $item->total]);

        // 4. Chart Data: Top 10 MDAs
        $mdaChartData = (clone $baseQuery)
            ->join('mdas', 'releases.mda_id', '=', 'mdas.id')
            ->select('mdas.name as label', DB::raw('CAST(SUM(releases.amount) AS DECIMAL(20,2)) as total'))
            ->groupBy('mdas.name')->orderBy('total', 'desc')->limit(10)->get()
            ->map(fn($item) => ['label' => $item->label, 'total' => (float) $item->total]);

        // 5. Release Trends (Monthly Velocity)
        $monthlyTrend = (clone $baseQuery)
            ->select(
                DB::raw("strftime('%m', release_date) as month_num"), // For SQLite. Use MONTH(release_date) for MySQL
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month_num')->orderBy('month_num')->get()
            ->pluck('total', 'month_num');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $trendValues = [];
        foreach (range(1, 12) as $m) {
            $key = str_pad($m, 2, '0', STR_PAD_LEFT);
            $trendValues[] = (float) ($monthlyTrend[$key] ?? 0);
        }

        // 6. Project Status Breakdown (Vetted, Approved, Released)
        // Assuming you have a 'status' column in your releases or linked projects table
        $statusData = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->get();

        $releases = $baseQuery->with(['mda', 'subhead.category'])
            ->orderBy('release_date', 'desc')->paginate(25);

        // 7. Dispatch comprehensive payload
        $this->dispatch('chartUpdated', [
            'sectors' => [
                'labels' => $sectorChartData->pluck('label')->toArray(),
                'values' => $sectorChartData->pluck('total')->toArray(),
            ],
            'mdas' => [
                'labels' => $mdaChartData->pluck('label')->toArray(),
                'values' => $mdaChartData->pluck('total')->toArray(),
            ],
            'burnRate' => $burnRate,
            'trends' => [
                'labels' => $months,
                'values' => $trendValues,
            ],
            'status' => [
                'labels' => $statusData->pluck('status')->toArray(),
                'values' => $statusData->pluck('count')->toArray(),
            ]
        ]);

        return view('livewire.admin.release-analytics', [
            'releases'        => $releases,
            'categories'      => Category::select('id', 'type')->get()->unique('type'),
            'sectorChartData' => $sectorChartData,
            'mdaChartData'    => $mdaChartData,
            'stats'           => $stats,
            'burnRate'        => $burnRate,
            'trendLabels'     => $months,
            'trendValues'     => $trendValues,
            'statusData'      => $statusData
        ])->layout('layouts.app');
    }
    protected function applyFilters($query)
    {
        if ($this->search) {
            $query->where(function($q) {
                $q->where('releases.narration', 'like', '%' . $this->search . '%')
                  ->orWhere('releases.reference_no', 'like', '%' . $this->search . '%')
                  ->orWhere('releases.mda_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryId) {
            $category = Category::find($this->categoryId);
            if ($category) {
                $query->whereHas('subhead.category', fn($q) => $q->where('type', $category->type));
            }
        }

        if ($this->minAmount) {
            $query->where('releases.amount', '>=', $this->minAmount);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('releases.release_date', [$this->startDate, $this->endDate]);
        }
    }
}