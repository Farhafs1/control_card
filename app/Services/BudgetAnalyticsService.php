<?php

namespace App\Services;

use App\Models\Subhead;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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
     * THE QUERY ENGINE (OPTIMIZED FOR PRODUCTION)
     *
     * Key changes vs. original:
     *  - select() restricts columns fetched from subheads (avoids SELECT *)
     *  - JOIN on mdas replaces ->with('mda') to eliminate the N+1 eager-load
     *  - mda sector is read from the joined column, not from a relationship accessor
     */
    public function getFilteredPerformance($quarter = 'all', $type = null, $category = null, $groupBy = 'category', $year = null)
    {
        $year     = $year ?? \App\Models\Setting::current()->fiscal_year;
        $settings = \App\Models\Setting::current();

        $query = Subhead::select([
                's.id',
                's.subhead_code',
                's.fiscal_year',
                's.approved_provision',
                's.virement_provision',
                's.supplementary_provision',
                's.additional_provision',
                // Pull sector directly from the join — avoids the ->mda->sector N+1 call
                'mdas.sector as mda_sector',
            ])
            ->from('subheads as s')
            ->join('mdas', 'mdas.id', '=', 's.mda_id')
            ->where('s.fiscal_year', $year)
            ->when($category, function ($q) use ($category) {
                if ($category === 'Expenditure_Capital') {
                    return $q->whereRaw('LENGTH(s.subhead_code) = 10');
                }
                $prefix = $this->getPrefixForCategory($category);
                if ($prefix) {
                    return $q->where('s.subhead_code', 'like', $prefix . '%')
                             ->whereRaw('LENGTH(s.subhead_code) = 8');
                }
                return $q;
            })
            ->withSum([
                'releases as actual_spent' => function ($q) use ($quarter) {
                    $q->where('is_cancelled', false);
                    if ($quarter !== 'all') {
                        $q->where('quarter', (int) $quarter);
                    }
                }
            ], 'amount');

        $rawSubheads = $query->get();

        return $rawSubheads->groupBy(function ($item) use ($groupBy) {
            // Use the already-joined column — no relationship traversal
            return ($groupBy === 'sector')
                ? ($item->mda_sector ?? 'Other')
                : $this->determineCategory($item->subhead_code);
        })->map(function ($group, $label) use ($settings) {

            $firstItem = $group->first();
            if (!$firstItem) return null;

            $totalBudget = (float) $group->sum(fn($item) =>
                (float) $item->approved_provision +
                (float) $item->virement_provision +
                (float) $item->supplementary_provision +
                (float) $item->additional_provision
            );

            $totalActual  = (float) $group->sum('actual_spent');
            $isRevenue    = str_starts_with((string) $firstItem->subhead_code, '1');
            $itemType     = $isRevenue ? 'Revenue' : 'Expenditure';
            $varianceData = $this->calculateVariance($totalBudget, $totalActual, $itemType);
            $performance  = (float) $varianceData->percentage;
            $tier         = $this->getPerformanceTier($performance);

            return (object) [
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
                'currency'      => $settings->currency_symbol,
            ];
        })
        ->filter()
        ->when($type, fn($c) => $c->where('type', $type))
        ->values();
    }

    /**
     * PHASE 1 HELPER: Maps category labels back to GFSM prefixes for DB-level filtering.
     */
    private function getPrefixForCategory($category)
    {
        return match ($category) {
            'Revenue_FAAC'            => '11',
            'Revenue_IGR'             => '12',
            'Revenue_Aid_Grant'       => '13',
            'Revenue_Capital_Receipt' => '14',
            'Expenditure_Personnel'   => '21',
            'Expenditure_Overhead'    => '22',
            'Expenditure_Capital'     => null,
            default                   => null,
        };
    }

    /**
     * Applies Government Standard Tiering to a performance percentage.
     */
    protected function getPerformanceTier($percentage)
    {
        $percentage = (float) $percentage;

        if ($percentage >= ($this->benchmarks['high'] ?? 80)) {
            return (object) [
                'label'    => 'High',
                'color'    => 'success',
                'icon'     => 'fa-check-circle',
                'bg_class' => 'bg-emerald-100 text-emerald-800',
            ];
        }

        if ($percentage >= ($this->benchmarks['moderate'] ?? 50)) {
            return (object) [
                'label'    => 'Moderate',
                'color'    => 'warning',
                'icon'     => 'fa-exclamation-circle',
                'bg_class' => 'bg-amber-100 text-amber-800',
            ];
        }

        return (object) [
            'label'    => 'Low',
            'color'    => 'danger',
            'icon'     => 'fa-times-circle',
            'bg_class' => 'bg-rose-100 text-rose-800',
        ];
    }

    /**
     * DASHBOARD AGGREGATOR
     */
    public function getSummaryStats($quarter = 'all')
    {
        $allData       = $this->getFilteredPerformance($quarter);
        $openingBalance = (float) ($this->settings->opening_balance ?? 0);

        $revenue     = $allData->where('type', 'Revenue');
        $expenditure = $allData->where('type', 'Expenditure');

        $revBudget = $revenue->sum('budget');
        $revActual = $revenue->sum('actual');
        $expBudget = $expenditure->sum('budget');
        $expActual = $expenditure->sum('actual');

        return [
            'opening_balance'   => $openingBalance,
            'revenue'           => (array) $this->calculateVariance($revBudget, $revActual, 'Revenue'),
            'expenditure'       => (array) $this->calculateVariance($expBudget, $expActual, 'Expenditure'),
            'net_cash_position' => ($openingBalance + $revActual) - $expActual,
        ];
    }

    /**
     * RANKING ENGINE
     */
    public function getMDARanking($quarter = 'all', $type = 'Expenditure')
    {
        return $this->getFilteredPerformance($quarter, $type, null, 'sector')
            ->map(function ($item) {
                return [
                    'mda'          => $item->display_label,
                    'total_budget' => $item->budget,
                    'total_actual' => $item->actual,
                    'variance'     => $item->variance,
                    'percentage'   => $item->percentage,
                    'status'       => $item->status,
                    'label'        => $item->status_label,
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
        $current  = $this->getSummaryStats($currentQ);
        $previous = $this->getSummaryStats($previousQ);

        return [
            'revenue_growth'  => $this->calculateGrowth($previous['revenue']['actual'], $current['revenue']['actual']),
            'spending_growth' => $this->calculateGrowth($previous['expenditure']['actual'], $current['expenditure']['actual']),
            'net_change'      => $current['net_cash_position'] - $previous['net_cash_position'],
        ];
    }

    private function calculateGrowth($old, $new)
    {
        if ($old <= 0) return $new > 0 ? 100 : 0;
        return round((($new - $old) / $old) * 100, 2);
    }

    /**
     * LEVEL 1 LOGIC: Macro Aggregation
     */
    public function getLevel1Analysis($quarter = 'all')
    {
        $allData = $this->getFilteredPerformance($quarter);

        $groups = [
            'Revenue'     => ['Revenue FAAC', 'Revenue IGR', 'Revenue Aid Grant', 'Revenue Capital Receipt'],
            'Expenditure' => ['Expenditure Personnel', 'Expenditure Overhead', 'Expenditure Capital'],
        ];

        $analysis = [];
        foreach ($groups as $level1Name => $level2Categories) {
            // display_label has underscores replaced with spaces already
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
                'breakdown'    => $this->formatBreakdown($subset),
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
                'count'  => $items->sum('item_count'),
            ];
        });
    }

    /**
     * LEVEL 2 LOGIC: Sectoral & MDA Deep Dive
     */
    public function getSectoralDeepDive($quarter = 'all', $sectorName = null)
    {
        $allData = $this->getFilteredPerformance($quarter, null, null, 'sector');

        if ($sectorName) {
            $allData = $allData->where('display_label', $sectorName);
        }

        return $allData->map(function ($sectorItem) {
            return [
                'sector'       => $sectorItem->display_label,
                'total_budget' => $sectorItem->budget,
                'total_actual' => $sectorItem->actual,
                'percentage'   => $sectorItem->percentage,
                'health_status'=> $sectorItem->status,
                'item_count'   => $sectorItem->item_count,
            ];
        })->sortByDesc('total_actual')->values();
    }

    /**
     * UNIVERSAL VARIANCE HELPER
     */
    public function calculateVariance($budget, $actual, $type = 'Expenditure')
    {
        $budget = (float) $budget;
        $actual = (float) $actual;

        if ($budget > 0) {
            $percentage = ($actual / $budget) * 100;
        } else {
            $percentage = $actual > 0 ? 101 : 0;
        }

        $amount = $budget - $actual;
        $tier   = $this->getPerformanceTier($percentage);

        $status = 'neutral';
        if ($type === 'Revenue') {
            $status = match (true) {
                $percentage >= 100 => 'success',
                $percentage >= 75  => 'info',
                default            => 'warning',
            };
        } else {
            $status = match (true) {
                $percentage > 100 => 'danger',
                $percentage >= 90 => 'warning',
                default           => 'success',
            };
        }

        return (object) [
            'budget'      => $budget,
            'actual'      => $actual,
            'amount'      => $amount,
            'percentage'  => round($percentage, 2),
            'status'      => $status,
            'label'       => $this->getStatusLabel($status),
            'tier_label'  => $tier->label,
            'tier_icon'   => $tier->icon,
            'is_overspent'=> ($type === 'Expenditure' && $actual > $budget),
            'is_shortfall'=> ($type === 'Revenue' && $actual < $budget),
        ];
    }

    private function getStatusLabel($status)
    {
        return match ($status) {
            'success' => 'On Track',
            'warning' => 'Underperforming',
            'danger'  => 'Overspent',
            'info'    => 'Satisfactory',
            default   => 'No Data',
        };
    }

    /**
     * THE CATEGORY MAPPER
     */
    private function determineCategory($code)
    {
        $cleanCode = trim((string) $code);
        $length    = strlen($cleanCode);

        if (empty($cleanCode)) return 'Unclassified';
        if ($length === 10)    return 'Expenditure_Capital';

        if (str_starts_with($cleanCode, '21')) return 'Expenditure_Personnel';
        if (str_starts_with($cleanCode, '22')) return 'Expenditure_Overhead';

        if ($length === 8) {
            if (str_starts_with($cleanCode, '11')) return 'Revenue_FAAC';
            if (str_starts_with($cleanCode, '12')) return 'Revenue_IGR';
            if (str_starts_with($cleanCode, '13')) return 'Revenue_Aid_Grant';
            if (str_starts_with($cleanCode, '14')) return 'Revenue_Capital_Receipt';
        }

        if ($length < 2) return 'Unclassified_Internal';

        return 'Sector_' . substr($cleanCode, 0, 2);
    }

    /**
     * PHASE 2: COMPARATIVE ANALYSIS ENGINE
     */
    public function getComparativeRanking($quarter = 'all', $type = 'Expenditure')
    {
        $data              = $this->getFilteredPerformance($quarter, $type, null, 'sector');
        $totalGlobalBudget = $data->sum('budget');

        $enrichedData = $data->map(function ($item) use ($totalGlobalBudget) {
            $budgetShare   = $totalGlobalBudget > 0 ? ($item->budget / $totalGlobalBudget) * 100 : 0;
            $weightedScore = $item->percentage * ($budgetShare / 100);

            return (object) array_merge((array) $item, [
                'budget_share'   => round($budgetShare, 2),
                'weighted_score' => round($weightedScore, 2),
                'is_significant' => $budgetShare > 5,
            ]);
        });

        $byValue      = $enrichedData->sortByDesc('actual')->values();
        $byEfficiency = $enrichedData->sortByDesc('weighted_score')->values();

        return [
            'spending_ranking'   => $byValue,
            'efficiency_ranking' => $byEfficiency,
            'meta' => [
                'total_volume' => $totalGlobalBudget,
                'top_spender'  => $byValue->first()->display_label ?? 'N/A',
                'most_critical'=> $byEfficiency->first()->display_label ?? 'N/A',
            ],
        ];
    }

    /**
     * PHASE 3, ITEM 1: EXCO-READY AGGREGATED REPORT
     */
    public function getExcoReport($filters)
    {
        $settings = \App\Models\Setting::current();

        $quarter  = $filters['quarter'] ?? 'all';
        $type     = $filters['type'] ?? null;
        $category = $filters['category'] ?? null;
        $groupBy  = (!empty($category)) ? 'sector' : ($filters['groupBy'] ?? 'category');

        $reportData    = $this->getFilteredPerformance($quarter, $type, $category, $groupBy, $settings->fiscal_year);
        $globalBudget  = $reportData->sum('budget');
        $globalActual  = $reportData->sum('actual');
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
                'current_lens'     => $groupBy,
            ],
            'rows' => $reportData->map(function ($row) {
                $tier = $this->getPerformanceTier($row->percentage);
                return [
                    'label'       => $row->display_label,
                    'approved'    => $row->budget,
                    'actual'      => $row->actual,
                    'performance' => round($row->percentage, 1),
                    'status'      => $tier->color,
                    'indicator'   => $tier->icon,
                    'status_text' => $tier->label,
                    'item_count'  => $row->item_count,
                ];
            })->sortByDesc('approved')->values(),
        ];
    }

    /**
     * PHASE 1 & 3: GLOBAL SWITCH COORDINATOR
     */
    public function getExcoDashboardState($filters = [])
    {
        $quarter  = $filters['quarter'] ?? 'all';
        $type     = $filters['type'] ?? null;
        $category = $filters['category'] ?? null;

        $performanceData = $this->getFilteredPerformance($quarter, $type, $category);
        $rankings        = $this->getComparativeRanking($quarter, $type);
        $macroAnalysis   = $this->getLevel1Analysis($quarter);
        $summary         = $this->getSummaryStats($quarter);

        return [
            'context' => [
                'period'      => $quarter === 'all' ? 'Full Year' : "Quarter $quarter",
                'scope'       => $category ?? ($type ?? 'All Departments'),
                'year'        => $this->settings->fiscal_year,
                'is_filtered' => ($quarter !== 'all' || $type || $category),
            ],
            'stats'    => $summary,
            'table'    => $performanceData,
            'rankings' => $rankings,
            'macro'    => $macroAnalysis,
        ];
    }

    /**
     * PHASE 3, ITEM 3: EXECUTIVE EXPORT DATA PREP
     */
    public function getExecutiveHandoutData($quarter = 'all', $type = 'Expenditure')
    {
        $summary     = $this->getSummaryStats($quarter);
        $tableData   = $this->getFilteredPerformance($quarter, $type, null, 'category');
        $rankings    = $this->getComparativeRanking($quarter, $type);
        $topPerformers    = collect($rankings['efficiency_ranking'])->take(5);
        $bottomPerformers = collect($rankings['efficiency_ranking'])->reverse()->take(5);

        return [
            'meta' => [
                'title'        => 'EXCO Budget Performance Brief',
                'fiscal_year'  => $this->settings->fiscal_year,
                'period'       => $quarter === 'all' ? 'Full Year' : "Quarter $quarter",
                'generated_at' => now()->format('d M Y, H:i'),
                'context'      => $type,
            ],
            'summary_cards' => [
                'budget'       => $summary[strtolower($type)]['budget'],
                'actual'       => $summary[strtolower($type)]['actual'],
                'performance'  => $summary[strtolower($type)]['percentage'],
                'status_label' => $summary[strtolower($type)]['label'],
            ],
            'main_table'      => $tableData,
            'executive_brief' => [
                'high_performers' => $topPerformers,
                'low_performers'  => $bottomPerformers,
            ],
        ];
    }

    public function getStats($quarter = 'all', $year = null)
    {
        $year           = $year ?? \App\Models\Setting::current()->fiscal_year;
        $openingBalance = (float) ($this->settings->opening_balance ?? 0);

        $allPerformanceData = $this->getFilteredPerformance($quarter, null, null, 'category', $year);

        $revenueItems     = $allPerformanceData->where('type', 'Revenue');
        $revBudget        = (float) $revenueItems->sum('budget');
        $revActual        = (float) $revenueItems->sum('actual');

        $expenditureItems = $allPerformanceData->where('type', 'Expenditure');
        $expBudget        = (float) $expenditureItems->sum('budget');
        $expActual        = (float) $expenditureItems->sum('actual');

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
            'opening_balance'   => $openingBalance,
        ];
    }

    /**
     * OPTIMIZED: getComparativeRankings
     *
     * Changed from strftime('%m', release_date) date math to WHERE quarter = ?
     * This matches the indexed `quarter` integer column used everywhere else,
     * works identically on SQLite (local) and MySQL (production), and is index-friendly.
     */
    public function getComparativeRankings($quarter = 'all')
    {
        return \App\Models\Release::query()
            ->where('is_cancelled', false)
            ->join('mdas', 'releases.mda_id', '=', 'mdas.id')
            ->leftJoin('subheads', 'releases.subhead_id', '=', 'subheads.id')
            ->select('mdas.mda_code', 'mdas.name')
            ->selectRaw('SUM(releases.amount) as total_actual')
            ->selectRaw('SUM(
                COALESCE(subheads.approved_provision, 0) +
                COALESCE(subheads.virement_provision, 0) +
                COALESCE(subheads.supplementary_provision, 0) +
                COALESCE(subheads.additional_provision, 0)
            ) as total_budget')
            // Use the indexed `quarter` integer column instead of computed strftime expressions
            ->when($quarter !== 'all', fn($q) => $q->where('releases.quarter', (int) $quarter))
            ->groupBy('mdas.mda_code', 'mdas.name')
            ->get()
            ->map(function ($item) use ($quarter) {
                $actual          = (float) $item->total_actual;
                $annualBudget    = (float) $item->total_budget;
                $comparativeBudget = ($quarter !== 'all') ? ($annualBudget / 4) : $annualBudget;
                $performance     = ($comparativeBudget > 0) ? ($actual / $comparativeBudget) * 100 : 0;

                $item->weighted_score  = ($performance * 0.7) + (($actual / 1_000_000) * 0.3);
                $item->actual          = $actual;
                $item->performance     = $performance;
                $item->is_significant  = $actual > 100_000_000;

                $tier              = $this->getPerformanceTier($performance);
                $item->status      = $tier->color;
                $item->status_label = $tier->label;

                return $item;
            })
            ->sortByDesc('weighted_score')
            ->values();
    }

    /**
     * OPTIMIZED: getRankingsBySubheadLogic
     *
     * Changed quarterly filter from strftime('%m', r.release_date) to
     * WHERE r.quarter = ? — uses the indexed integer column, works on
     * both SQLite and MySQL without engine-specific date functions.
     */
    public function getRankingsBySubheadLogic(array $constraints): Collection
    {
        $quarter = $constraints['quarter'];
        $rules   = $constraints['rules'];

        $query = DB::table('mdas as m')
            ->select('m.id', 'm.name', 'm.mda_code')
            ->join('releases as r', 'm.id', '=', 'r.mda_id')
            ->join('subheads as s', 'r.subhead_id', '=', 's.id')
            ->selectRaw('SUM(r.amount) as actual')
            ->selectRaw('SUM(s.approved_provision) as provision')
            ->where('r.is_cancelled', false)
            ->groupBy('m.id', 'm.name', 'm.mda_code');

        if ($rules === 'all_expenditure') {
            $query->where(function ($q) {
                $q->whereRaw('LENGTH(s.subhead_code) = 10')
                  ->orWhere(function ($sq) {
                      $sq->whereRaw('LENGTH(s.subhead_code) = 8')
                         ->whereRaw("s.subhead_code NOT LIKE '1%'");
                  });
            });
        } elseif (is_array($rules)) {
            $query->whereRaw('LENGTH(s.subhead_code) = ?', [$rules['length']]);
            if (!empty($rules['prefixes'])) {
                $query->where(function ($q) use ($rules) {
                    foreach ($rules['prefixes'] as $prefix) {
                        $q->orWhere('s.subhead_code', 'LIKE', $prefix . '%');
                    }
                });
            }
        }

        // Use indexed integer `quarter` column instead of strftime date expressions
        if ($quarter !== 'all') {
            $query->where('r.quarter', (int) $quarter);
        }

        return $query->get()->map(function ($item) {
            $actual   = (float) $item->actual;
            $provision = (float) $item->provision;

            $item->performance_percentage = $provision > 0 ? ($actual / $provision) * 100 : 0;
            $item->weighted_score         = min($item->performance_percentage, 100);

            return $item;
        });
    }

    /**
     * getComparativeData — unchanged (delegates to getPeriodStats)
     */
    public function getComparativeData(array $pA, array $pB, string $type): Collection
    {
        $dataA = $this->getPeriodStats($pA, $type);
        $dataB = $this->getPeriodStats($pB, $type);

        return $dataA->map(function ($itemA) use ($dataB) {
            $itemB = $dataB->firstWhere('mda_id', $itemA->mda_id);

            $actualA       = (float) ($itemA->total_actual ?? 0);
            $actualB       = (float) ($itemB->total_actual ?? 0);
            $variance      = $actualB - $actualA;
            $percentChange = $actualA > 0 ? ($variance / $actualA) * 100 : ($actualB > 0 ? 100 : 0);

            return (object) [
                'mda_id'        => $itemA->mda_id,
                'name'          => $itemA->name,
                'mda_code'      => $itemA->mda_code,
                'actual_a'      => $actualA,
                'actual_b'      => $actualB,
                'variance'      => $variance,
                'percent_change'=> $percentChange,
                'perf_a'        => $itemA->performance ?? 0,
                'perf_b'        => $itemB->performance ?? 0,
            ];
        });
    }

    /**
     * OPTIMIZED: getPeriodStats
     *
     * Replaced whereYear('r.release_date', ...) + strftime quarter math with
     * WHERE fiscal_year = ? and WHERE quarter = ? — both are indexed integers
     * that work on SQLite and MySQL without date parsing overhead.
     */
    private function getPeriodStats(array $period, string $type): Collection
    {
        $query = DB::table('mdas as m')
            ->join('releases as r', 'm.id', '=', 'r.mda_id')
            ->join('subheads as s', 'r.subhead_id', '=', 's.id')
            ->select('m.id as mda_id', 'm.name', 'm.mda_code')
            ->selectRaw('SUM(r.amount) as total_actual')
            ->selectRaw('CASE WHEN SUM(s.approved_provision) > 0 THEN (SUM(r.amount) / SUM(s.approved_provision)) * 100 ELSE 0 END as performance')
            ->where('r.is_cancelled', false)
            // Use fiscal_year integer column instead of whereYear() on a date column
            ->where('s.fiscal_year', $period['year']);

        if ($period['quarter'] !== 'all') {
            // Use indexed `quarter` integer column instead of computed strftime expression
            $query->where('r.quarter', (int) $period['quarter']);
        }

        return $query->groupBy('m.id', 'm.name', 'm.mda_code')->get();
    }

    /**
     * OPTIMIZED: getMultiPeriodData
     *
     * Original ran one DB query PER MDA PER period inside a nested loop.
     * Now runs ONE query per period using conditional aggregation (SUM CASE WHEN),
     * then joins the results in PHP — reduces DB round-trips from N*P to P.
     *
     * N = number of MDAs, P = number of periods selected.
     */
    public function getMultiPeriodData(array $periods, string $type): Collection
    {
        $mdas = DB::table('mdas')->select('id', 'name', 'mda_code')->get()->keyBy('id');

        // Build one query per period, fetch all MDAs at once, then pivot in PHP
        $periodTotals = [];
        foreach ($periods as $index => $p) {
            $query = DB::table('releases as r')
                ->join('subheads as s', 'r.subhead_id', '=', 's.id')
                ->select('r.mda_id')
                ->selectRaw('SUM(r.amount) as total')
                ->where('r.is_cancelled', false)
                ->where('s.fiscal_year', $p['year']);

            if ($p['quarter'] !== 'all') {
                $query->where('r.quarter', (int) $p['quarter']);
            }

            // Index by mda_id for O(1) lookup when building the result below
            $periodTotals[$index] = $query->groupBy('r.mda_id')->get()->keyBy('mda_id');
        }

        return $mdas->map(function ($mda) use ($periods, $periodTotals) {
            $values = [];

            foreach ($periods as $index => $p) {
                $values["p_$index"] = (float) ($periodTotals[$index][$mda->id]->total ?? 0);
            }

            $mda->values             = $values;
            $first                   = reset($values);
            $last                    = end($values);
            $mda->total_variance     = $last - $first;
            $mda->variance_percentage = $first > 0 ? (($last - $first) / $first) * 100 : 0;

            return $mda;
        })->values();
    }
}