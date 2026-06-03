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

    // ADD THIS LINE HERE:
    public $quarter = '';

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

            $prompt = "You are the Chief Budget Officer for Katsina State. 
            Analyze this executive expenditure summary:
            
            $summary
            
            Provide a professional 5-paragraph executive brief with specific headings:
            1. SPENDING TRENDS: What sectors are dominating?
            2. THE IMPACT OF THE CURRENT TREND: What does this trend achievement means for the Administration?
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
        
        // REFACTORED: This now uses the quarter-based applyFilters 
        // to ensure the AI only sees the data for the selected quarter.
        $this->applyFilters($baseQuery);

        $total = $baseQuery->sum('amount');
        $count = $baseQuery->count();
        
        // Get Top 10 MDAs
        // Logic preserved, but performance is boosted by the quarter filter in applyFilters
        $topMdas = (clone $baseQuery)->join('mdas', 'releases.mda_id', '=', 'mdas.id')
                    ->select('mdas.name', DB::raw('SUM(amount) as total'))
                    ->groupBy('mdas.name')->orderBy('total', 'desc')->limit(10)->get();
        
        $mdaString = $topMdas->map(fn($m) => "{$m->name} (₦" . number_format($m->total, 2) . ")")->implode(', ');

        // Get Top Category
        $topCat = (clone $baseQuery)->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
                    ->join('categories', 'subheads.category_id', '=', 'categories.id')
                    ->select('categories.type', DB::raw('SUM(releases.amount) as total'))
                    ->groupBy('categories.type')->orderBy('total', 'desc')->first();

        // NEW: We explicitly tell the AI which quarter this data belongs to.
        $quarterLabel = "Quarter " . $this->quarter;

        return "DATA SUMMARY:
        - Reporting Period: $quarterLabel
        - Total Expenditure: ₦" . number_format($total, 2) . "
        - Volume: $count total releases
        - Leading Sector: " . ($topCat->type ?? 'N/A') . "
        - Top 10 Spending MDAs: $mdaString";
    }

    public function exportReport($format = 'pdf')
    {
        // Determine which route name to use based on the format
        $routeName = ($format === 'ppt') 
            ? 'admin.expenditure.ppt' 
            : 'admin.expenditure.export';

        return redirect()->route($routeName, [
            'search'     => $this->search,
            'minAmount'  => $this->minAmount,
            'categoryId' => $this->categoryId,
            
            // REFACTORED: Removed startDate and endDate.
            // We now pass the integer quarter to the export controller.
            'q'          => $this->quarter, 
            
            'format'     => $format,
            'ai_text'    => $this->aiAnalysis 
        ]);
    }

    public function render()
    {
        // 1. Static Category Options (Matches your manual mapping)
        $categoryOptions = [
            'Expenditure_Capital'   => 'Capital Expenditure',
            'Expenditure_Personnel' => 'Personnel Cost',
            'Expenditure_Overhead'  => 'Overhead Cost',
            'Revenue_FAAC'          => 'FAAC Revenue',
            'Revenue_IGR'           => 'IGR Revenue',
        ];

        // 2. Initialize Base Query with your specific logic
        $baseQuery = Release::query()->select('releases.*');

        // 3. Apply the Category Logic (The "Codes" plan)
        if ($this->categoryId) {
            $baseQuery->whereHas('subhead', function($q) {
                if ($this->categoryId === 'Expenditure_Capital') {
                    $q->whereRaw('LENGTH(subhead_code) = 10');
                } 
                elseif ($this->categoryId === 'Expenditure_Personnel') {
                    $q->where('subhead_code', 'like', '21%')->whereRaw('LENGTH(subhead_code) != 10');
                }
                elseif ($this->categoryId === 'Expenditure_Overhead') {
                    $q->where('subhead_code', 'like', '22%')->whereRaw('LENGTH(subhead_code) != 10');
                }
                elseif (str_starts_with($this->categoryId, 'Revenue_')) {
                    $prefix = match($this->categoryId) {
                        'Revenue_FAAC' => '11',
                        'Revenue_IGR'  => '12',
                        default        => null
                    };
                    if ($prefix) {
                        $q->where('subhead_code', 'like', $prefix . '%')->whereRaw('LENGTH(subhead_code) = 8');
                    }
                }
            });
        }

        // 4. Apply standard filters (Search & Quarter)
        $this->applyFilters($baseQuery);

        // 5. Calculate Stats from the filtered query
        $totalReleased = (float) (clone $baseQuery)->sum('amount');
        $stats = [
            'total_value' => $totalReleased,
            'count'       => (clone $baseQuery)->count(),
            'avg_release' => (float) (clone $baseQuery)->avg('amount') ?? 0,
            'max_release' => (float) (clone $baseQuery)->max('amount') ?? 0,
        ];

        // 6. Chart Data (Cloning ensures filters stay applied)
        $sectorChartData = (clone $baseQuery)
            ->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
            ->join('categories', 'subheads.category_id', '=', 'categories.id')
            ->select('categories.type as label', DB::raw('SUM(releases.amount) as total'))
            ->groupBy('categories.type')->get();

        $mdaChartData = (clone $baseQuery)
            ->join('mdas', 'releases.mda_id', '=', 'mdas.id')
            ->select('mdas.name as label', DB::raw('SUM(releases.amount) as total'))
            ->groupBy('mdas.name')->orderBy('total', 'desc')->limit(10)->get();

        // $statusData = (clone $baseQuery)
        //     ->select('status', DB::raw('count(*) as count'))
        //     ->groupBy('status')->get();

        // 7. Dynamic Budget (Burn Rate)
        $approvedBudget = \App\Models\Subhead::query()
            ->where('fiscal_year', $this->fiscal_year ?? date('Y'))
            ->ofCategory($this->categoryId)
            ->sum(DB::raw('COALESCE(approved_provision, 0) + COALESCE(supplementary_provision, 0)'));

        $burnRate = $approvedBudget > 0 ? round(($totalReleased / $approvedBudget) * 100, 1) : 0;

        // 8. Quarterly Trend
        $quarterlyTrend = (clone $baseQuery)
            ->select('quarter', DB::raw('SUM(amount) as total'))
            ->groupBy('quarter')->orderBy('quarter')->pluck('total', 'quarter');
        $trendValues = collect(range(1, 4))->map(fn($q) => (float) ($quarterlyTrend[$q] ?? 0))->toArray();

        // 9. Final Results for Table
        $releases = $baseQuery->with(['mda', 'subhead.category'])
            ->orderBy('release_date', 'desc')->paginate(25);

        // 10. Dispatch to Frontend
        $this->dispatch('chartUpdated', [
            'burnRate' => $burnRate,
            'trends'   => ['labels' => ['Q1', 'Q2', 'Q3', 'Q4'], 'values' => $trendValues],
            'sectors'  => $sectorChartData,
            'mdas'     => $mdaChartData,
            // 'status'   => $statusData,
        ]);

        return view('livewire.admin.release-analytics', [
            'stats'           => $stats,
            'releases'        => $releases,
            'categories'      => $categoryOptions, // Uses your manual list now
            'sectorChartData' => $sectorChartData, 
            'mdaChartData'    => $mdaChartData,
            // 'statusData'      => $statusData,
            'burnRate'        => $burnRate,
            'trendLabels'     => ['Q1', 'Q2', 'Q3', 'Q4'],
            'trendValues'     => $trendValues,
            'aiAnalysis'      => $this->aiAnalysis,
        ]);
    }

    protected function applyFilters($query)
    {
        // 1. Search Logic (Preserved exactly as is)
        if ($this->search) {
            $query->where(function($q) {
                $q->where('releases.narration', 'like', '%' . $this->search . '%')
                ->orWhere('releases.reference_no', 'like', '%' . $this->search . '%')
                ->orWhere('releases.mda_code', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Category Filter (Preserved exactly as is)
        if ($this->categoryId) {
            $query->whereHas('subhead', function($q) {
                // 1. Handle Capital Expenditure (The 10-digit rule)
                if ($this->categoryId === 'Expenditure_Capital') {
                    $q->whereRaw('LENGTH(subhead_code) = 10');
                } 
                
                // 2. Handle Personnel (Prefix 21)
                elseif ($this->categoryId === 'Expenditure_Personnel') {
                    $q->where('subhead_code', 'like', '21%')
                    ->whereRaw('LENGTH(subhead_code) != 10');
                }

                // 3. Handle Overhead (Prefix 22)
                elseif ($this->categoryId === 'Expenditure_Overhead') {
                    $q->where('subhead_code', 'like', '22%')
                    ->whereRaw('LENGTH(subhead_code) != 10');
                }

                // 4. Handle Revenue Categories (8-digit rule + specific prefixes)
                elseif (str_starts_with($this->categoryId, 'Revenue_')) {
                    $prefix = match($this->categoryId) {
                        'Revenue_FAAC'            => '11',
                        'Revenue_IGR'             => '12',
                        'Revenue_Aid_Grant'       => '13',
                        'Revenue_Capital_Receipt' => '14',
                        default                   => null
                    };

                    if ($prefix) {
                        $q->where('subhead_code', 'like', $prefix . '%')
                        ->whereRaw('LENGTH(subhead_code) = 8');
                    }
                }
            });
        }

        // 3. Min Amount Filter (Preserved exactly as is)
        if ($this->minAmount) {
            $query->where('releases.amount', '>=', $this->minAmount);
        }

        /**
         * 4. REFACTORED: Quarterly Logic
         * We have removed the $startDate and $endDate checks.
         * Everything now filters through the indexed 'quarter' column.
         */
        if ($this->quarter) {
            $query->where('releases.quarter', $this->quarter);
        }
    }

    public function getStats()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);

        return [
            'total_value' => (float) $baseQuery->sum('amount'),
            'count'       => $baseQuery->count(),
            'avg_release' => (float) $baseQuery->avg('amount') ?? 0,
            'max_release' => (float) $baseQuery->max('amount') ?? 0,
        ];
    }

    public function getReleases()
    {
        $baseQuery = Release::query()->with(['mda', 'subhead.category']);
        $this->applyFilters($baseQuery);
        
        return $baseQuery->orderBy('release_date', 'desc')->paginate(25);
    }

    public function getSectorData()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);

        return $baseQuery->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
            ->join('categories', 'subheads.category_id', '=', 'categories.id')
            ->select('categories.type as label', DB::raw('SUM(releases.amount) as total'))
            ->groupBy('categories.type')
            ->get();
    }

    public function getMdaData()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);

        return $baseQuery->join('mdas', 'releases.mda_id', '=', 'mdas.id')
            ->select('mdas.name as label', DB::raw('SUM(releases.amount) as total'))
            ->groupBy('mdas.name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
    }

    public function calculateBurnRate()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);
        $totalReleased = (float) $baseQuery->sum('amount');

        $approvedBudget = \App\Models\Subhead::query()
            ->where('fiscal_year', date('Y'))
            ->ofCategory($this->categoryId)
            ->sum(DB::raw('COALESCE(approved_provision, 0) + COALESCE(supplementary_provision, 0)'));

        return $approvedBudget > 0 ? round(($totalReleased / $approvedBudget) * 100, 1) : 0;
    }

    public function getTrendData()
    {
        $baseQuery = Release::query();
        $this->applyFilters($baseQuery);

        $quarterlyTrend = $baseQuery->select('quarter', DB::raw('SUM(amount) as total'))
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->pluck('total', 'quarter');

        return [
            'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
            'values' => collect(range(1, 4))->map(fn($q) => (float) ($quarterlyTrend[$q] ?? 0))->toArray()
        ];
    }

    // public function getStatusData()
    // {
    //     $baseQuery = Release::query();
    //     $this->applyFilters($baseQuery);

    //     return $baseQuery->select('status', DB::raw('count(*) as count'))
    //         ->groupBy('status')
    //         ->get();
    // }
}