<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ParallelAnalyticsService
{
    /**
     * Determine budget line type and specific GFSM category based on code architecture.
     */
    public function classifySubhead(string $code): array
    {
        $cleanCode = trim($code);
        $length = strlen($cleanCode);

        // 1. Capital Expenditure Check (10 Digits)
        if ($length === 10) {
            return [
                'type' => 'Expenditure',
                'category' => 'Expenditure_Capital',
                'label' => 'Capital Expenditure'
            ];
        }

        // 2. Recurrent Expenditure Checks (Prefix 21 & 22)
        if (str_starts_with($cleanCode, '21')) {
            return [
                'type' => 'Expenditure',
                'category' => 'Expenditure_Personnel',
                'label' => 'Personnel Expenditure'
            ];
        }
        if (str_starts_with($cleanCode, '22')) {
            return [
                'type' => 'Expenditure',
                'category' => 'Expenditure_Overhead',
                'label' => 'Overhead Expenditure'
            ];
        }

        // 3. Revenue Category Checks (Prefix 11, 12, 13, 14)
        if (str_starts_with($cleanCode, '11')) {
            return [
                'type' => 'Revenue',
                'category' => 'Revenue_FAAC',
                'label' => 'FAAC Share'
            ];
        }
        if (str_starts_with($cleanCode, '12')) {
            return [
                'type' => 'Revenue',
                'category' => 'Revenue_IGR',
                'label' => 'Internal Generated Revenue (IGR)'
            ];
        }
        if (str_starts_with($cleanCode, '13')) {
            return [
                'type' => 'Revenue',
                'category' => 'Revenue_Aid_Grant',
                'label' => 'Aids & Grants'
            ];
        }
        if (str_starts_with($cleanCode, '14')) {
            return [
                'type' => 'Revenue',
                'category' => 'Revenue_Capital_Receipt',
                'label' => 'Capital Receipts'
            ];
        }

        return [
            'type' => 'Expenditure',
            'category' => 'Unclassified',
            'label' => 'Other Recurrent'
        ];
    }

    /**
     * Unified Core Math Engine for Variances
     */
    public function calculateVariance(float $budget, float $actual, string $type): array
    {
        $percentage = $budget > 0 ? ($actual / $budget) * 100 : ($actual > 0 ? 100.0 : 0.0);
        $variance = $budget - $actual;

        if ($type === 'Revenue') {
            $status = $percentage >= 100 ? 'success' : ($percentage >= 75 ? 'info' : 'warning');
            $label = $percentage >= 100 ? 'Target Met' : 'Shortfall';
        } else {
            $status = $percentage > 100 ? 'danger' : ($percentage >= 90 ? 'warning' : 'success');
            $label = $percentage > 100 ? 'Overspent' : 'On Track';
        }

        return [
            'budget' => $budget,
            'actual' => $actual,
            'variance' => $variance,
            'percentage' => round($percentage, 2),
            'status' => $status,
            'label' => $label
        ];
    }

    /**
     * MAIN PARALLEL PROCESSING HUB
     */
    public function getParallelDashboardState(array $filters): array
    {
        $settings = Setting::current();
        $year = $filters['year'] ?? $settings->fiscal_year;
        $quarter = $filters['quarter'] ?? 'all';
        $filterType = $filters['type'] ?? null;
        $filterCategory = $filters['category'] ?? null;
        $groupBy = $filters['groupBy'] ?? 'category';

        // 1. Core Transaction releases processing subquery
        $releasesSubquery = DB::table('releases')
            ->select('subhead_id', DB::raw('SUM(amount) as total_spent'))
            ->where('is_cancelled', false)
            ->when($quarter !== 'all', fn($q) => $q->where('quarter', (int)$quarter))
            ->groupBy('subhead_id');

        // FIXED SCHEMA POINTER: 's.description as subhead_name' maps to the database setup
        $rawSubheads = DB::table('subheads as s')
            ->select([
                's.id',
                's.subhead_code',
                's.description as subhead_name', 
                'm.name as mda_name',
                'm.sector as mda_functional_sector', 
                DB::raw('(COALESCE(s.approved_provision, 0) + 
                          COALESCE(s.virement_provision, 0) + 
                          COALESCE(s.supplementary_provision, 0) + 
                          COALESCE(s.additional_provision, 0)) as total_budget'),
                DB::raw('COALESCE(r_sums.total_spent, 0) as total_actual')
            ])
            ->join('mdas as m', 'm.id', '=', 's.mda_id')
            ->leftJoinSub($releasesSubquery, 'r_sums', 'r_sums.subhead_id', '=', 's.id')
            ->where('s.fiscal_year', $year)
            ->get();

        // 2. Trend tracking background generation (All 4 quarters processing simultaneously)
        $trendReleases = DB::table('releases')
            ->select('subhead_id', 'quarter', DB::raw('SUM(amount) as amount'))
            ->where('is_cancelled', false)
            ->groupBy('subhead_id', 'quarter')
            ->get()
            ->groupBy('subhead_id');

        // 3. Setup Array Memory Maps
        $globalStats = [
            'opening_balance' => (float) ($settings->opening_balance ?? 0),
            'revenue_total_budget' => 0, 'revenue_total_actual' => 0,
            'expenditure_total_budget' => 0, 'expenditure_total_actual' => 0,
            'segments' => []
        ];

        $sectoralDistribution = [];
        $mainTableGroupings = [];
        $trendTrackingMetrics = ['Q1' => 0, 'Q2' => 0, 'Q3' => 0, 'Q4' => 0];

        // 4. One Pass Memory Run
        foreach ($rawSubheads as $row) {
            $class = $this->classifySubhead($row->subhead_code);
            $lineType = $class['type'];
            $categoryKey = $class['category'];

            // Compute global statistics
            if ($lineType === 'Revenue') {
                $globalStats['revenue_total_budget'] += $row->total_budget;
                $globalStats['revenue_total_actual'] += $row->total_actual;
            } else {
                $globalStats['expenditure_total_budget'] += $row->total_budget;
                $globalStats['expenditure_total_actual'] += $row->total_actual;
            }

            // Segments map configuration
            if (!isset($globalStats['segments'][$categoryKey])) {
                $globalStats['segments'][$categoryKey] = ['budget' => 0, 'actual' => 0, 'label' => $class['label']];
            }
            $globalStats['segments'][$categoryKey]['budget'] += $row->total_budget;
            $globalStats['segments'][$categoryKey]['actual'] += $row->total_actual;

            // Map and parse the functional sectors from administrative assignments
            $mdaSector = $row->mda_functional_sector ? ucfirst(strtolower(trim($row->mda_functional_sector))) : 'Administrative';
            if (!isset($sectoralDistribution[$mdaSector])) {
                $sectoralDistribution[$mdaSector] = ['name' => $mdaSector, 'budget' => 0, 'actual' => 0];
            }
            $sectoralDistribution[$mdaSector]['budget'] += $row->total_budget;
            $sectoralDistribution[$mdaSector]['actual'] += $row->total_actual;

            // Aggregate quarterly performance trends for expenditures
            if ($lineType === 'Expenditure' && $trendReleases->has($row->id)) {
                foreach ($trendReleases->get($row->id) as $rel) {
                    $qKey = "Q" . $rel->quarter;
                    if (isset($trendTrackingMetrics[$qKey])) {
                        $trendTrackingMetrics[$qKey] += $rel->amount;
                    }
                }
            }

            // Apply filters for the table view
            if ($filterType && $lineType !== $filterType) continue;
            if ($filterCategory && $categoryKey !== $filterCategory) continue;

            $groupKey = match ($groupBy) {
                'mda'    => $row->mda_name,
                'sector' => $mdaSector,
                default  => $class['label']
            };

            if (!isset($mainTableGroupings[$groupKey])) {
                $mainTableGroupings[$groupKey] = [
                    'name' => $groupKey,
                    'total_budget' => 0,
                    'total_actual' => 0,
                    'type' => $lineType
                ];
            }
            $mainTableGroupings[$groupKey]['total_budget'] += $row->total_budget;
            $mainTableGroupings[$groupKey]['total_actual'] += $row->total_actual;
        }

        // 5. Build and format response collection array packs
        $formattedTable = collect($mainTableGroupings)->map(function ($group) {
            return array_merge($group, $this->calculateVariance($group['total_budget'], $group['total_actual'], $group['type']));
        })->values()->toArray();

        $formattedSectors = collect($sectoralDistribution)->map(function ($sector) {
            $percentage = $sector['budget'] > 0 ? ($sector['actual'] / $sector['budget']) * 100 : 0;
            return array_merge($sector, ['percentage' => round($percentage, 2)]);
        })->values()->toArray();

        $netCashPosition = ($globalStats['opening_balance'] + $globalStats['revenue_total_actual']) - $globalStats['expenditure_total_actual'];

        return [
            'stats' => [
                'opening_balance' => $globalStats['opening_balance'],
                'net_cash_position' => $netCashPosition,
                'revenue' => $this->calculateVariance($globalStats['revenue_total_budget'], $globalStats['revenue_total_actual'], 'Revenue'),
                'expenditure' => $this->calculateVariance($globalStats['expenditure_total_budget'], $globalStats['expenditure_total_actual'], 'Expenditure'),
                'segments' => collect($globalStats['segments'])->map(function($seg, $key) {
                    $type = str_starts_with($key, 'Revenue') ? 'Revenue' : 'Expenditure';
                    return array_merge($seg, $this->calculateVariance($seg['budget'], $seg['actual'], $type));
                })->toArray()
            ],
            'table' => $formattedTable,
            'sectors' => $formattedSectors,
            'trends' => $trendTrackingMetrics
        ];
    }
}