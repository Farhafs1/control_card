<?php

namespace App\Services;

use App\Models\{Mda, Category, Subhead, Release};
use Illuminate\Support\Facades\Auth;

class BoBudgetPerformanceService
{
    /**
     * Internal helper to get IDs of MDAs assigned to the logged-in BO.
     */
    private function getOfficerMdaIds()
    {
        $userId = Auth::id();
        $ids = \App\Models\Mda::where('user_id', $userId)->pluck('id')->toArray();
        
        return $ids;
    }

    /**
     * Report #1: Executive Overview (Scoped to BO's MDAs)
     */
    // In your BoBudgetPerformanceService class
    public function getExecutiveOverview($quarter = 'all')
    {
        // 1. Fetch subheads with their releases
        $data = \App\Models\Subhead::whereIn('mda_id', $this->getOfficerMdaIds())
            ->with(['releases' => function($q) use ($quarter) {
                if ($quarter !== 'all') {
                    $q->where('quarter', $quarter);
                }
            }])
            ->get();

        // 2. Group and calculate
        return $data->groupBy(function ($item) {
            $code = (string)$item->subhead_code;
            if (str_starts_with($code, '1'))  return 'REVENUE';
            if (str_starts_with($code, '21')) return 'PERSONNEL';
            if (str_starts_with($code, '22')) return 'OVERHEAD';
            return 'CAPITAL';
        })->map(function ($group, $label) {
            $totalProv = $group->sum('approved_provision') + $group->sum('additional_provision');
            
            // Sum releases from the related releases collection
            $totalActual = $group->sum(function($subhead) {
                return $subhead->releases->sum('amount');
            });

            // Note: If you specifically need Q1, Q2, Q3, Q4 breakdown 
            // you must filter the releases collection for each
            return (object)[
                'label'  => $label,
                'budget' => $totalProv,
                'q1'     => $group->sum(fn($s) => $s->releases->where('quarter', 1)->sum('amount')),
                'q2'     => $group->sum(fn($s) => $s->releases->where('quarter', 2)->sum('amount')),
                'q3'     => $group->sum(fn($s) => $s->releases->where('quarter', 3)->sum('amount')),
                'q4'     => $group->sum(fn($s) => $s->releases->where('quarter', 4)->sum('amount')),
                'total'  => $totalActual,
                'perf'   => $totalProv > 0 ? ($totalActual / $totalProv) * 100 : 0
            ];
        });
    }

    /**
     * Helper to query budget performance data for the executive overview
     */
    private function queryBudgetPerformance($quarter = 'all')
    {
        $mdaIds = $this->getOfficerMdaIds();

        return \App\Models\Subhead::query()
            ->whereIn('mda_id', $mdaIds)
            ->select([
                'category as category_label', // Use your actual column name here
                'approved_provision as provision',
                'q1_actual',
                'q2_actual',
                'q3_actual',
                'q4_actual'
            ])
            ->get();
    }

    /**
     * Report #2: Detailed MDA & Subhead Performance (Scoped to BO's MDAs)
     */
    public function getDetailedReport($quarter, $categoryId)
    {
        $mdaIds = $this->getOfficerMdaIds();

        return Mda::whereIn('id', $mdaIds)
            ->whereHas('subheads', function ($query) use ($categoryId) {
                // Apply the code pattern filtering at the 'whereHas' level
                // so we don't load MDAs that have no subheads in this category
                $this->applyCategoryFilter($query, $categoryId);
            })
            ->with(['subheads' => function ($query) use ($quarter, $categoryId) {
                $this->applyCategoryFilter($query, $categoryId);
                
                $query->withSum(['releases' => function ($q) use ($quarter) {
                    if ($quarter !== 'all') {
                        $q->where('quarter', $quarter);
                    }
                }], 'amount');
            }])
            ->get()
            ->map(function ($mda) {
                $mda->total_provision = $mda->subheads->sum(fn($s) => (float)$s->approved_provision + (float)$s->additional_provision);
                $mda->total_actual = $mda->subheads->sum('releases_sum_amount') ?? 0;
                $mda->performance_pct = $mda->total_provision > 0 
                    ? ($mda->total_actual / $mda->total_provision) * 100 
                    : 0;
                return $mda;
            });
    }

    /**
     * Helper to apply filtering logic consistently
     */
    private function applyCategoryFilter($query, $categoryId)
    {
        $type = strtoupper($categoryId);
        
        if ($type === 'CAPITAL') {
            return $query->whereRaw("LENGTH(subhead_code) > 8");
        }
        
        $prefix = match ($type) {
            'REVENUE'   => '1%',
            'PERSONNEL' => '21%',
            'OVERHEAD'  => '22%',
            default     => null
        };

        if ($prefix) {
            return $query->where('subhead_code', 'LIKE', $prefix);
        }
        
        return $query;
    }

    public function getRankingReport($quarter, $limit = null, $direction = 'desc', $sortBy = 'total_spend')
    {
        $mdaIds = $this->getOfficerMdaIds();
        if (empty($mdaIds)) return [];

        // 1. Fetch MDAs with their releases pre-loaded
        $mdas = Mda::whereIn('id', $mdaIds)
            ->with(['releases' => function($q) use ($quarter) {
                if ($quarter !== 'all') {
                    $q->where('quarter', (int) $quarter);
                }
            }])
            ->get();

        // 2. Map the data in memory
        $results = $mdas->map(function($mda) {
            $releases = $mda->releases;
            
            // Explicit calculations
            $rev = $releases->filter(fn($r) => str_starts_with((string)$r->subhead_code, '1'))->sum('amount');
            $per = $releases->filter(fn($r) => str_starts_with((string)$r->subhead_code, '21'))->sum('amount');
            $ove = $releases->filter(fn($r) => str_starts_with((string)$r->subhead_code, '22'))->sum('amount');
            $cap = $releases->filter(fn($r) => strlen((string)$r->subhead_code) > 8)->sum('amount');
            $total = $releases->sum('amount');

            // This array now matches your Blade's keys exactly:
            // $mda['mda_code'], $mda['mda_name'], $mda['revenue'], etc.
            return [
                'mda_name'    => $mda->name,
                'mda_code'    => $mda->mda_code,
                'revenue'     => (float)$rev,
                'personnel'   => (float)$per,
                'overhead'    => (float)$ove,
                'capital'     => (float)$cap,
                'total_spend' => (float)$total,
            ];
        });

        // 3. Sort and limit
        $sorted = ($direction === 'desc') ? $results->sortByDesc($sortBy) : $results->sortBy($sortBy);
        
        return $limit ? $sorted->take($limit)->values()->toArray() : $sorted->values()->toArray();
    }

    public function getQuarterLabel($quarter)
    {
        return match ($quarter) {
            1, '1' => 'First Quarter',
            2, '2' => 'Second Quarter',
            3, '3' => 'Third Quarter',
            4, '4' => 'Fourth Quarter',
            'all'  => 'Annual (All Quarters)',
            default => 'Full Year',
        };
    }


    /**
     * Report: Quarterly Summary (Single Quarter)
     */
    public function getQuarterlyReport($quarter)
    {
        // 1. Fetch subheads with their releases for the specific quarter
        $subheads = \App\Models\Subhead::whereIn('mda_id', $this->getOfficerMdaIds())
            ->with(['releases' => function($q) use ($quarter) {
                if ($quarter !== 'all') {
                    $q->where('quarter', $quarter);
                }
            }])
            ->get();

        // 2. Group by category and map to the format expected by the view
        $groupedData = $subheads->groupBy(function ($item) {
            $code = (string)$item->subhead_code;
            if (str_starts_with($code, '1'))  return 'REVENUE';
            if (str_starts_with($code, '21')) return 'PERSONNEL';
            if (str_starts_with($code, '22')) return 'OVERHEAD';
            return 'CAPITAL';
        })->map(function ($group, $label) {
            $app = $group->sum('approved_provision');
            $add = $group->sum('additional_provision');
            $totalProv = $app + $add;
            $actual = $group->sum(fn($s) => $s->releases->sum('amount'));

            return [
                'label'      => $label,
                'approved'   => $app,
                'additional' => $add,
                'total_prov' => $totalProv,
                'actual'     => $actual,
                'perf'       => $totalProv > 0 ? ($actual / $totalProv) * 100 : 0
            ];
        });

        // 3. Return as the structure the view is looking for
        return [
            'full_list' => $groupedData->values()
        ];
    }

}