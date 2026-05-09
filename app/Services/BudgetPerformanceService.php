<?php

namespace App\Services;

use App\Models\{Mda, Category, Subhead, Release, Setting};
use Illuminate\Support\Facades\DB;

class BudgetPerformanceService
{
    /**
     * REFACTORED: Uses the 'quarter' column for speed.
     * Logic for Report #1: Executive Overview
     */
    public function getExecutiveOverview($quarter)
    {
        return Category::with(['subheads' => function ($query) use ($quarter) {
            $query->withSum(['releases' => function ($q) use ($quarter) {
                if ($quarter !== 'all') {
                    $q->where('quarter', $quarter);
                }
            }], 'amount');
        }])
        ->get()
        ->map(function ($category) {
            $totalProvision = $category->subheads->sum(fn($s) => 
                (float)$s->approved_provision + (float)$s->additional_provision
            );

            $totalActual = $category->subheads->sum('releases_sum_amount') ?? 0;

            return (object)[
                'category_name'   => $category->type,
                'total_provision' => $totalProvision,
                'total_actual'    => $totalActual,
                'performance_pct' => $totalProvision > 0 ? ($totalActual / $totalProvision) * 100 : 0,
            ];
        });
    }

    /**
     * REFACTORED: Handles "all" and optimized relationships.
     * Logic for Report #2: Detailed MDA & Subhead Performance
     */
    public function getDetailedReport($quarter, $categoryId)
    {
        return Mda::with(['subheads' => function ($query) use ($quarter, $categoryId) {
            $query->where('category_id', $categoryId)
                ->withSum(['releases' => function ($q) use ($quarter) {
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
        })
        ->filter(fn($mda) => $mda->subheads->count() > 0);
    }

    /**
     * REFACTORED Ranking: High Performance + Dynamic Category Sorting
     * @param string $sortBy options: 'total_spend', 'revenue', 'personnel', 'overhead', 'capital'
     */
    public function getRankingReport($quarter, $limit = null, $direction = 'desc', $sortBy = 'total_spend')
    {
        $query = Mda::select('id', 'name', 'mda_code');

        // Categorization mapping
        $types = [
            'revenue'   => 'REVENUE',
            'personnel' => 'PERSONNEL',
            'overhead'  => 'OVERHEAD', // Will handle OVERHEAD or RECURRENT
            'capital'   => 'CAPITAL'
        ];

        // 1. Efficiently sum each category via the database
        foreach ($types as $key => $type) {
            $query->withSum(['releases as ' . $key => function($q) use ($quarter, $type) {
                $q->whereHas('subhead.category', function($cq) use ($type) {
                    if ($type === 'OVERHEAD') {
                        $cq->where('type', 'LIKE', '%OVERHEAD%')
                           ->orWhere('type', 'LIKE', '%RECURRENT%');
                    } else {
                        $cq->where('type', 'LIKE', '%' . $type . '%');
                    }
                });
                
                if ($quarter !== 'all') {
                    $q->where('quarter', $quarter);
                }
            }], 'amount');
        }

        // 2. Sum the absolute total for each MDA
        $query->withSum(['releases as total_spend' => function($q) use ($quarter) {
            if ($quarter !== 'all') {
                $q->where('quarter', $quarter);
            }
        }], 'amount');

        $results = $query->get()->map(function($mda) {
            $rev = (float)$mda->revenue;
            $per = (float)$mda->personnel;
            $ove = (float)$mda->overhead;
            $cap = (float)$mda->capital;
            $tot = (float)$mda->total_spend;

            return [
                'mda_name'    => $mda->name,
                'mda_code'    => $mda->mda_code,
                'revenue'     => $rev,
                'personnel'   => $per,
                'overhead'    => $ove,
                'capital'     => $cap,
                // Ensures "Other" doesn't go negative due to rounding
                'other'       => max(0, $tot - ($rev + $per + $ove + $cap)),
                'total_spend' => $tot,
            ];
        });

        // 3. Dynamic Sort: Sort by chosen category or total
        $sorted = ($direction === 'desc') 
            ? $results->sortByDesc($sortBy) 
            : $results->sortBy($sortBy);

        return $limit ? $sorted->take($limit)->values()->toArray() : $sorted->values()->toArray();
    }

    /**
     * Helper for display labels in exports or UI
     */
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
}