<?php

namespace App\Services;

use App\Models\Subhead;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BudgetAnalyticsService
{
    protected $settings;
    
    // Phase 1, Item 3: Government Standard Benchmarks
    protected $benchmarks = [
        'high'     => 80,
        'moderate' => 50,
    ];

    public function __construct()
    {
        $this->settings = Setting::current();
    }

    /**
     * THE QUERY ENGINE (UPDATED PHASE 1)
     * Handles cascading filters with database-level precision.
     */
    public function getFilteredPerformance($quarter = 'all', $type = null, $category = null, $groupBy = 'category', $year = null)
    {
        // Fallback to current settings
        $year = $year ?? \App\Models\Setting::current()->fiscal_year;
        $settings = \App\Models\Setting::current();

        $query = Subhead::where('fiscal_year', $year)
            ->when($category, function($q) use ($category) {
                // 1. Capital Expenditure (10 digits)
                if ($category === 'Expenditure_Capital') {
                    return $q->whereRaw('LENGTH(subhead_code) = 10');
                }

                // 2. Revenue Categories (Must be 8 digits AND have the prefix)
                $prefix = $this->getPrefixForCategory($category);
                if ($prefix) {
                    return $q->where('subhead_code', 'like', $prefix . '%')
                            ->whereRaw('LENGTH(subhead_code) = 8'); // ADD THIS LINE
                }

                return $q;
            })
            ->withSum(['releases as actual_spent' => function ($query) use ($quarter) {
                // CRITICAL: Exclude cancelled releases from the sum
                $query->where('is_cancelled', false);
                // 1. Check the original string first
                    if ($quarter !== 'all') {
                        
                        // 2. Now that we know it's a number (1, 2, 3, or 4), cast it
                        $q_val = (int) $quarter; 

                        $driver = config('database.default');
                        if ($driver === 'sqlite') {
                            // SQLite math
                            $query->whereRaw('(( (strftime("%m", date(release_date)) + 0) + 2) / 3) = ?', [$q_val]);
                        } else {
                            // MySQL math
                            $query->whereRaw('CEIL(MONTH(release_date) / 3) = ?', [$q_val]);
                        }
                    }
                    // If it IS 'all', the code skips this block and shows the full year (Correct!)
            }], 'amount')
            ->with('mda');

        $rawSubheads = $query->get();

        return $rawSubheads->groupBy(function ($item) use ($groupBy) {
            return ($groupBy === 'sector') 
                ? ($item->mda->sector ?? 'Other') 
                : $this->determineCategory($item->subhead_code);
        })->map(function ($group, $label) use ($settings) {
            
            // 1. Safety Check: Skip empty groups early
            $firstItem = $group->first();
            if (!$firstItem) {
                return null; 
            }

            // 2. Provision Summing with Float Casting
            $totalBudget = (float) $group->sum(function($item) {
                return (float)$item->approved_provision + 
                    (float)$item->virement_provision + 
                    (float)$item->supplementary_provision + 
                    (float)$item->additional_provision;
            });
            
            // 3. Actual Spent Summing (Force Float to prevent nulls)
            $totalActual = (float) $group->sum('actual_spent');
            
            // 4. Robust Revenue Detection
            $isRevenue = str_starts_with((string)$firstItem->subhead_code, '1');
            $itemType = $isRevenue ? 'Revenue' : 'Expenditure';

            // 5. Calculate Variance using the Service Engine
            $varianceData = $this->calculateVariance($totalBudget, $totalActual, $itemType);
            
            // 6. Resolve Visual Tiers (Labels, Icons, Colors)
            $performance = (float) $varianceData->percentage;
            $tier = $this->getPerformanceTier($performance);

            return (object)[
                'display_label' => str_replace('_', ' ', $label),
                'type'          => $itemType,
                'budget'        => $totalBudget,
                'actual'        => $totalActual,
                'variance'      => $varianceData->amount,
                'percentage'    => $performance,
                'status'        => $tier->color,        
                'status_label'  => $tier->label,        
                'status_icon'   => $tier->icon,
                'item_count'    => $group->count(),
                'currency'      => $settings->currency_symbol
            ];
        })
        ->filter() // Removes the nulls from step 1
        ->when($type, fn($c) => $c->where('type', $type))
        ->values();
    }
    /**
     * PHASE 1 HELPER: Maps category labels back to GFSM prefixes for DB-level filtering.
     */
    private function getPrefixForCategory($category)
    {
        return match($category) {
            // Revenue (GFSM Prefix)
            'Revenue_FAAC'            => '11',
            'Revenue_IGR'             => '12',
            'Revenue_Aid_Grant'       => '13',
            'Revenue_Capital_Receipt' => '14',

            // Recurrent Expenditure
            'Expenditure_Personnel'   => '21',
            'Expenditure_Overhead'    => '22',

            // Explicitly return null for Capital or Unclassified 
            // because they rely on length/logic rather than a simple prefix
            'Expenditure_Capital'     => null, 
            default                   => null
        };
    }
    /**
     * Applies Government Standard Tiering to a performance percentage.
     * Synchronized with Phase 1, Item 3 benchmarks.
     */
    protected function getPerformanceTier($percentage)
    {
        if ($percentage >= $this->benchmarks['high']) {
            return (object)[
                'label' => 'High',
                'color' => 'success', // Green
                'icon'  => 'fa-check-circle'
            ];
        }

        if ($percentage >= $this->benchmarks['moderate']) {
            return (object)[
                'label' => 'Moderate',
                'color' => 'warning', // Amber/Yellow
                'icon'  => 'fa-exclamation-circle'
            ];
        }

        return (object)[
            'label' => 'Low',
            'color' => 'danger', // Red
            'icon'  => 'fa-times-circle'
        ];
    }

    /**
     * DASHBOARD AGGREGATOR
     * Provides the high-level stats for the "Sleek" cards at the top of the UI.
     */
    public function getSummaryStats($quarter = 'all')
    {
        // Fetch aggregated data from the Query Engine
        $allData = $this->getFilteredPerformance($quarter);
        $openingBalance = (float) ($this->settings->opening_balance ?? 0);

        $revenue = $allData->where('type', 'Revenue');
        $expenditure = $allData->where('type', 'Expenditure');

        // Sum up the pre-aggregated group totals
        $revBudget = $revenue->sum('budget');
        $revActual = $revenue->sum('actual');
        $expBudget = $expenditure->sum('budget');
        $expActual = $expenditure->sum('actual');

        return [
            'opening_balance'   => $openingBalance,
            'revenue'           => (array) $this->calculateVariance($revBudget, $revActual, 'Revenue'),
            'expenditure'       => (array) $this->calculateVariance($expBudget, $expActual, 'Expenditure'),
            'net_cash_position' => ($openingBalance + $revActual) - $expActual
        ];
    }
    /**
     * RANKING ENGINE
     * Used for "Top Spender" or "Highest Variance" MDA analysis.
     */
    public function getMDARanking($quarter = 'all', $type = 'Expenditure')
    {
        // We leverage getFilteredPerformance with 'sector' groupBy to ensure mda_name access
        return $this->getFilteredPerformance($quarter, $type, null, 'sector')
            ->map(function ($item) use ($type) {
                // Since getFilteredPerformance already calculates variance per group,
                // we map it into a ranking-friendly format.
                return [
                    'mda'          => $item->display_label,
                    'total_budget' => $item->budget,
                    'total_actual' => $item->actual,
                    'variance'     => $item->variance,
                    'percentage'   => $item->percentage,
                    'status'       => $item->status,
                    'label'        => $item->status_label
                ];
            })
            ->sortByDesc('total_actual')
            ->values();
    }

    /**
     * TREND ENGINE: Side-by-Side Quarter Comparison
     */
    public function getQuarterlyTrend($currentQ, $previousQ)
    {
        $current = $this->getSummaryStats($currentQ);
        $previous = $this->getSummaryStats($previousQ);

        return [
            'revenue_growth'  => $this->calculateGrowth($previous['revenue']['actual'], $current['revenue']['actual']),
            'spending_growth' => $this->calculateGrowth($previous['expenditure']['actual'], $current['expenditure']['actual']),
            'net_change'      => $current['net_cash_position'] - $previous['net_cash_position']
        ];
    }

    private function calculateGrowth($old, $new)
    {
        if ($old <= 0) return $new > 0 ? 100 : 0;
        return round((($new - $old) / $old) * 100, 2);
    }

    /**
     * LEVEL 1 LOGIC: Macro Aggregation
     * Groups specific categories into the top-level Revenue/Expenditure buckets.
     */
    public function getLevel1Analysis($quarter = 'all')
    {
        $allData = $this->getFilteredPerformance($quarter);
        
        $groups = [
            'Revenue'     => ['Revenue_FAAC', 'Revenue_IGR', 'Revenue_Aid_Grant', 'Revenue_Capital_Receipt'],
            'Expenditure' => ['Expenditure_Personnel', 'Expenditure_Overhead', 'Expenditure_Capital']
        ];

        $analysis = [];
        foreach ($groups as $level1Name => $level2Categories) {
            $subset = $allData->whereIn('display_label', $level2Categories);
            $budget = $subset->sum('budget');
            $actual = $subset->sum('actual');
            
            $varianceData = $this->calculateVariance($budget, $actual, $level1Name);

            $analysis[$level1Name] = [
                'total_budget' => $budget,
                'total_actual' => $actual,
                'variance'     => $varianceData->amount,
                'percentage'   => $varianceData->percentage,
                'status'       => $varianceData->status,
                'status_label' => $varianceData->label,
                'breakdown'    => $this->formatBreakdown($subset)
            ];
        }
        return $analysis;
    }

    private function formatBreakdown($collection)
    {
        return $collection->groupBy('display_label')->map(function ($items) {
            return [
                'budget' => $items->sum('budget'),
                'actual' => $items->sum('actual'),
                'count'  => $items->sum('item_count')
            ];
        });
    }

    /**
     * LEVEL 2 LOGIC: Sectoral & MDA Deep Dive
     */
    public function getSectoralDeepDive($quarter = 'all', $sectorName = null)
    {
        // Fetch data grouped by sector
        $allData = $this->getFilteredPerformance($quarter, null, null, 'sector');

        if ($sectorName) {
            $allData = $allData->where('display_label', $sectorName);
        }

        return $allData->map(function ($sectorItem) {
            // This leverages the pre-calculated aggregates from the main engine
            return [
                'sector'         => $sectorItem->display_label,
                'total_budget'   => $sectorItem->budget,
                'total_actual'   => $sectorItem->actual,
                'percentage'     => $sectorItem->percentage,
                'health_status'  => $sectorItem->status,
                'item_count'     => $sectorItem->item_count
            ];
        })->sortByDesc('total_actual')->values();
    }
    /**
     * UNIVERSAL VARIANCE HELPER
     * Calculates the spread and assigns a status based on type (Revenue vs Expenditure).
     */
    public function calculateVariance($budget, $actual, $type = 'Expenditure')
    {
        $amount = (float)$budget - (float)$actual;
        $percentage = $budget > 0 ? ($actual / $budget) * 100 : 0;
        
        // Fetch the standard tier (High/Moderate/Low) based on the 80/50 rule
        $tier = $this->getPerformanceTier($percentage);
        
        // Define specific health statuses based on accounting context
        $status = 'neutral';
        if ($type === 'Revenue') {
            if ($percentage >= 100) $status = 'success';
            elseif ($percentage >= 75) $status = 'info';
            else $status = 'warning';
        } else {
            // Expenditure context: Over 100% is dangerous (overspent)
            if ($percentage > 100) $status = 'danger';
            elseif ($percentage >= 90) $status = 'warning';
            else $status = 'success';
        }

        return (object)[
            'budget'     => $budget,
            'actual'     => $actual,
            'amount'     => $amount,
            'percentage' => round($percentage, 2),
            'status'     => $status,        // Contextual color (success, info, warning, danger)
            'label'      => $this->getStatusLabel($status),
            'tier_label' => $tier->label,   // Absolute performance (High, Moderate, Low)
            'tier_icon'  => $tier->icon
        ];
    }

    /**
     * Translates status slugs into human-readable dashboard labels.
     */
    private function getStatusLabel($status)
    {
        return match($status) {
            'success' => 'On Track',
            'warning' => 'Underperforming',
            'danger'  => 'Overspent',
            'info'    => 'Satisfactory',
            default   => 'No Data'
        };
    }

    /**
     * THE CATEGORY MAPPER
     * Identifies budget lines based on GFSM code prefixes and string length.
     */
    private function determineCategory($code)
    {
        // 1. Sanitize once
        $cleanCode = trim((string)$code);
        $length = strlen($cleanCode);

        // 2. Safety Check
        if (empty($cleanCode)) return 'Unclassified';

        // 3. Capital Expenditure (10-digit rule)
        if ($length === 10) return 'Expenditure_Capital';

        // 4. Recurrent Expenditure
        if (str_starts_with($cleanCode, '21')) return 'Expenditure_Personnel';
        if (str_starts_with($cleanCode, '22')) return 'Expenditure_Overhead';

        // 5. Revenue (8-digit validation)
        if ($length === 8) {
            if (str_starts_with($cleanCode, '11')) return 'Revenue_FAAC';
            if (str_starts_with($cleanCode, '12')) return 'Revenue_IGR';
            if (str_starts_with($cleanCode, '13')) return 'Revenue_Aid_Grant';
            if (str_starts_with($cleanCode, '14')) return 'Revenue_Capital_Receipt';
        }

        // 6. The Fixed Fallback
        // Only return 'Unclassified_Internal' if the code is too short to even have a prefix
        if ($length < 2) return 'Unclassified_Internal';

        // Otherwise, use your Dynamic Sector logic
        return 'Sector_' . substr($cleanCode, 0, 2);
    }

   
    /**
     * PHASE 2: COMPARATIVE ANALYSIS ENGINE
     * Provides data for the Dual-Ranking Toggle (Value vs. Efficiency)
     * PHASE 2, ITEM 2: WEIGHTED ANALYSIS
     * Adds Fiscal Significance (Budget Share) to the ranking logic.
     */
    public function getComparativeRanking($quarter = 'all', $type = 'Expenditure')
    {
        // 1. Get the baseline data
        $data = $this->getFilteredPerformance($quarter, $type, null, 'sector');
        
        // 2. Calculate the Total Global Budget for this specific type (Revenue or Expenditure)
        // This is needed to calculate the "Share" (%) of each MDA/Sector
        $totalGlobalBudget = $data->sum('budget');

        // 3. Map and Inject Weighted Metrics
        $enrichedData = $data->map(function($item) use ($totalGlobalBudget) {
            // Fiscal Significance: What % of the total budget does this entity control?
            $budgetShare = $totalGlobalBudget > 0 
                ? ($item->budget / $totalGlobalBudget) * 100 
                : 0;

            // Weighted Score: A balance between performance and size.
            // Example: 50% performance on 10B is more significant than 90% on 1M.
            $weightedScore = ($item->percentage * ($budgetShare / 100));

            return (object) array_merge((array)$item, [
                'budget_share'   => round($budgetShare, 2),
                'weighted_score' => round($weightedScore, 2),
                'is_significant' => $budgetShare > 5 // Flag if entity controls > 5% of total budget
            ]);
        });

        // 4. Ranking By Value (Cash Flow)
        $byValue = $enrichedData->sortByDesc('actual')->values();

        // 5. Ranking By Efficiency (Weighted)
        // We sort by weighted_score so that large, high-performing MDAs rise to the top
        $byEfficiency = $enrichedData->sortByDesc('weighted_score')->values();

        return [
            'spending_ranking'   => $byValue,
            'efficiency_ranking' => $byEfficiency,
            'meta' => [
                'total_volume'   => $totalGlobalBudget,
                'top_spender'    => $byValue->first()->display_label ?? 'N/A',
                'most_critical'  => $byEfficiency->first()->display_label ?? 'N/A',
            ]
        ];
    }

    /**
     * PHASE 3, ITEM 1: EXCO-READY AGGREGATED REPORT
     * Distills the data into "The Big Picture" for cabinet-level presentation.
     */
    public function getExcoReport($filters)
    {
        // 1. Load dynamic system settings (Year, Currency, etc.)
        $settings = \App\Models\Setting::current();
        
        $quarter = $filters['quarter'] ?? 'all';
        $type = $filters['type'] ?? null;
        $category = $filters['category'] ?? null;
        
        /**
         * DYNAMIC GROUPING
         * Automatically switches the "lens" if a specific category is chosen.
         */
        $groupBy = (!empty($category)) ? 'sector' : ($filters['groupBy'] ?? 'category');

        // 2. Fetch aggregated data using the active fiscal year from settings
        // Ensure getFilteredPerformance is updated to accept $settings->fiscal_year
        $reportData = $this->getFilteredPerformance(
            $quarter, 
            $type, 
            $category, 
            $groupBy, 
            $settings->fiscal_year
        );

        // 3. Generate Global Totals
        $globalBudget = $reportData->sum('budget');
        $globalActual = $reportData->sum('actual');
        
        // Calculate variance using your Expenditure logic
        $globalVariance = $this->calculateVariance($globalBudget, $globalActual, 'Expenditure');

        return [
            'summary' => [
                'total_approved'   => $globalBudget,
                'total_actual'     => $globalActual,
                'performance'      => $globalVariance->percentage,
                'status_label'     => $globalVariance->label,
                'status_color'     => $globalVariance->status,
                'fiscal_year'      => $settings->fiscal_year,
                'currency_symbol'  => $settings->currency_symbol,
                'reporting_period' => $quarter === 'all' ? 'Full Year' : "Quarter $quarter",
                'current_lens'     => $groupBy
            ],
            
            'rows' => $reportData->map(function($row) {
                // Pull specific tier for ICON and COLOR from your engine
                $tier = $this->getPerformanceTier($row->percentage);

                return [
                    'label'       => $row->display_label,
                    'approved'    => $row->budget,
                    'actual'      => $row->actual,
                    'performance' => round($row->percentage, 1),
                    'status'      => $tier->color,
                    'indicator'   => $tier->icon,
                    'status_text' => $tier->label,
                    'item_count'  => $row->item_count
                ];
            })->sortByDesc('approved')->values()
        ];
    }

    /**
     * PHASE 1 & 3: GLOBAL SWITCH COORDINATOR
     * This ensures every module reflects the "Global Filters" selected by the user.
     */
    public function getExcoDashboardState($filters = [])
    {
        // 1. Extract Global Switches with defaults
        // These values come directly from your Livewire dropdowns
        $quarter  = $filters['quarter'] ?? 'all';
        $type     = $filters['type'] ?? null;      // Revenue or Expenditure
        $category = $filters['category'] ?? null;  // Personnel, Overhead, Capital, etc.

        // 2. The Waterfall Effect: Core Dataset
        // We fetch the main table data using the optimized Query Engine from the previous step.
        $performanceData = $this->getFilteredPerformance($quarter, $type, $category);

        // 3. Synchronized Rankings
        // We pass the SAME quarter and type so that if HE filters for "Q2", 
        // the rankings show Q2 performance, not the full year.
        $rankings = $this->getComparativeRanking($quarter, $type);

        // 4. Macro Level 1 Analysis
        // This provides the "Big Picture" (Total Rev vs Total Exp) for the selected quarter.
        $macroAnalysis = $this->getLevel1Analysis($quarter);

        // 5. Summary Stats (The Sleek Cards)
        // This ensures the Net Cash Position is calculated based on the selected period.
        $summary = $this->getSummaryStats($quarter);

        return [
            'context' => [
                'period'   => $quarter === 'all' ? 'Full Year' : "Quarter $quarter",
                'scope'    => $category ?? ($type ?? 'All Departments'),
                'year'     => $this->settings->fiscal_year,
                'is_filtered' => ($quarter !== 'all' || $type || $category),
            ],
            'stats'    => $summary,          // Feeds the top cards
            'table'    => $performanceData,  // Feeds the aggregated main table
            'rankings' => $rankings,       // Feeds the new Ranking Page/Section
            'macro'    => $macroAnalysis,    // Feeds the breakdown charts
        ];
    }

    /**
     * PHASE 3, ITEM 3: EXECUTIVE EXPORT DATA PREP
     * Prepares a distilled, single-page data structure for PDF/Meeting handouts.
     */
    public function getExecutiveHandoutData($quarter = 'all', $type = 'Expenditure')
    {
        // 1. Get the Big Picture (Summary Stats)
        $summary = $this->getSummaryStats($quarter);
        
        // 2. Get the Aggregated Table (Filtered by Type)
        // We use 'category' here as it's the standard EXCO presentation format
        $tableData = $this->getFilteredPerformance($quarter, $type, null, 'category');

        // 3. Get the Top 5 "Fiscally Significant" Performers (Weighted)
        // We limit to 5 to ensure the PDF stays on one page
        $rankings = $this->getComparativeRanking($quarter, $type);
        $topPerformers = collect($rankings['efficiency_ranking'])->take(5);
        $bottomPerformers = collect($rankings['efficiency_ranking'])->reverse()->take(5);

        return [
            'meta' => [
                'title' => 'EXCO Budget Performance Brief',
                'fiscal_year' => $this->settings->fiscal_year,
                'period' => $quarter === 'all' ? 'Full Year' : "Quarter $quarter",
                'generated_at' => now()->format('d M Y, H:i'),
                'context' => $type
            ],
            'summary_cards' => [
                'budget' => $summary[strtolower($type)]['budget'],
                'actual' => $summary[strtolower($type)]['actual'],
                'performance' => $summary[strtolower($type)]['percentage'],
                'status_label' => $summary[strtolower($type)]['label']
            ],
            'main_table' => $tableData,
            'executive_brief' => [
                'high_performers' => $topPerformers,
                'low_performers' => $bottomPerformers
            ]
        ];
    }

    public function getStats($quarter = 'all', $year = null)
    {
        // 1. Setup Context
        $year = $year ?? \App\Models\Setting::current()->fiscal_year;
        $openingBalance = (float) ($this->settings->opening_balance ?? 0);

        // 2. Consistent Quarter Filter Logic
        $applyQuarter = function ($query) use ($quarter) {
            // Always exclude cancelled releases
            $query->where('is_cancelled', false);

            if ($quarter !== 'all') {
                $driver = config('database.default');
                if ($driver === 'sqlite') {
                    $query->whereRaw('(( (strftime("%m", date(release_date)) + 0) + 2) / 3) = ?', [$quarter]);
                } else {
                    $query->whereRaw('CEIL(MONTH(release_date) / 3) = ?', [$quarter]);
                }
            }
        };

        // 3. Fetch Raw Totals (Budget vs Actual) for Revenue
        $revenueSubheads = \App\Models\Subhead::where('fiscal_year', $year)
            ->where('subhead_code', 'like', '1%')
            ->withSum(['releases as actual_spent' => $applyQuarter], 'amount')
            ->get();

        $revBudget = (float) $revenueSubheads->sum(fn($item) => 
            $item->approved_provision + $item->virement_provision + 
            $item->supplementary_provision + $item->additional_provision
        );
        $revActual = (float) $revenueSubheads->sum('actual_spent');

        // 4. Fetch Raw Totals (Budget vs Actual) for Expenditure
        $expenditureSubheads = \App\Models\Subhead::where('fiscal_year', $year)
            ->where('subhead_code', 'not like', '1%')
            ->withSum(['releases as actual_spent' => $applyQuarter], 'amount')
            ->get();

        $expBudget = (float) $expenditureSubheads->sum(fn($item) => 
            $item->approved_provision + $item->virement_provision + 
            $item->supplementary_provision + $item->additional_provision
        );
        $expActual = (float) $expenditureSubheads->sum('actual_spent');

        // 5. Use your existing Variance Engine to get Status, Labels, and Percentages
        $revAnalysis = $this->calculateVariance($revBudget, $revActual, 'Revenue');
        $expAnalysis = $this->calculateVariance($expBudget, $expActual, 'Expenditure');

        return [
            'revenue' => [
                'actual'     => $revActual,
                'budget'     => $revBudget,
                'percentage' => $revAnalysis->percentage,
                'status'     => $revAnalysis->status,
                'label'      => $revAnalysis->label,
            ],
            'expenditure' => [
                'actual'     => $expActual,
                'budget'     => $expBudget,
                'percentage' => $expAnalysis->percentage,
                'status'     => $expAnalysis->status,
                'label'      => $expAnalysis->label,
            ],
            'net_cash_position' => ($openingBalance + $revActual) - $expActual,
            'opening_balance'   => $openingBalance
        ];
    }
    public function getComparativeRankings($quarter = 'all')
    {
        return \App\Models\Release::query()
            // 1. Join MDAs for official names and Subheads for provisions
            ->join('mdas', 'releases.mda_id', '=', 'mdas.id')
            ->leftJoin('subheads', 'releases.subhead_id', '=', 'subheads.id')
            ->select(
                'mdas.mda_code', 
                'mdas.name'
            )
            ->selectRaw('SUM(releases.amount) as total_actual')
            // Summing Approved, Supplementary, and Additional provisions
            ->selectRaw('SUM(
                COALESCE(subheads.approved_provision, 0) + 
                COALESCE(subheads.supplementary_provision, 0) + 
                COALESCE(subheads.additional_provision, 0)
            ) as total_budget')
            // 2. Apply Quarterly Filtering
            ->when($quarter !== 'all', function ($query) use ($quarter) {
                return $query->where('releases.quarter', $quarter);
            })
            ->groupBy('mdas.mda_code', 'mdas.name')
            ->get()
            ->map(function ($item) use ($quarter) {
                $actual = (float) $item->total_actual;
                $annualBudget = (float) $item->total_budget;

                /**
                 * 3. Quarterly Performance Logic
                 * If filtering by a single quarter, we compare actuals against 25% of the annual budget
                 * to prevent "Efficiency" scores from looking artificially low.
                 */
                $comparativeBudget = ($quarter !== 'all') ? ($annualBudget / 4) : $annualBudget;
                
                $performance = ($comparativeBudget > 0) ? ($actual / $comparativeBudget) * 100 : 0;

                /**
                 * 4. Institutional Weighting Logic
                 * 70% weight to utilization (Efficiency)
                 * 30% weight to scale (Absolute spending in Millions)
                 */
                $item->weighted_score = ($performance * 0.7) + (($actual / 1000000) * 0.3);

                // 5. UI Flags & Status (Required by your Blade template)
                $item->actual = $actual;
                $item->performance = $performance;
                
                // Flags MDAs with releases over 100 Million as "High Impact"
                $item->is_significant = $actual > 100000000; 

                // Tailwind status colors based on execution efficiency
                $item->status = match(true) {
                    $performance >= 90 => 'emerald',
                    $performance >= 50 => 'amber',
                    default            => 'rose',
                };

                return $item;
            })
            // Default sort by weighted score; Livewire handles the 'Spending' toggle sort
            ->sortByDesc('weighted_score')
            ->values(); 
    }
}