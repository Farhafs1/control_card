<?php

namespace App\Services;

use App\Models\MDA;
use App\Models\Category;
use App\Models\Subhead;
use Illuminate\Support\Facades\DB;

class BudgetPerformanceService
{
    /**
     * Get date range for a specific quarter
     */
    public function getQuarterDates($quarter, $year = null)
    {
        $year = $year ?? date('Y');
        return match ((int)$quarter) {
            1 => ["$year-01-01", "$year-03-31"],
            2 => ["$year-04-01", "$year-06-30"],
            3 => ["$year-07-01", "$year-09-30"],
            4 => ["$year-10-01", "$year-12-31"],
            default => ["$year-01-01", "$year-12-31"],
        };
    }

    /**
     * Logic for Report #1: Executive Overview
     * Aggregates state-wide performance across the main categories (Revenue, Personnel, etc.)
     */
    public function getExecutiveOverview($quarter)
    {
        $range = $this->getQuarterDates($quarter);

        return Category::with(['subheads' => function ($query) use ($range) {
            $query->withSum(['releases' => function ($q) use ($range) {
                $q->whereBetween('release_date', $range);
            }], 'amount');
        }])
        ->get()
        ->map(function ($category) {
            $totalProvision = $category->subheads->sum(fn($s) => 
                (float)$s->approved_provision + (float)$s->additional_provision
            );

            $totalActual = $category->subheads->sum('releases_sum_amount');

            return (object)[
                'category_name'   => $category->type,
                'total_provision' => $totalProvision,
                'total_actual'    => $totalActual,
                'performance_pct' => $totalProvision > 0 ? ($totalActual / $totalProvision) * 100 : 0,
            ];
        });
    }

    /**
     * Logic for Report #2: Detailed MDA & Subhead Performance
     */
    public function getDetailedReport($quarter, $categoryId)
    {
        $range = $this->getQuarterDates($quarter);

        return MDA::with(['subheads' => function ($query) use ($range, $categoryId) {
            $query->where('category_id', $categoryId)
                ->withSum(['releases' => function ($q) use ($range) {
                    $q->whereBetween('release_date', $range);
                }], 'amount');
        }])
        ->get()
        ->map(function ($mda) {
            $mda->total_provision = $mda->subheads->sum(fn($s) => (float)$s->approved_provision + (float)$s->additional_provision);
            $mda->total_actual = $mda->subheads->sum('releases_sum_amount');
            $mda->performance_pct = $mda->total_provision > 0 
                ? ($mda->total_actual / $mda->total_provision) * 100 
                : 0;
            return $mda;
        })
        ->filter(fn($mda) => $mda->subheads->count() > 0);
    }

    /**
     * Logic for Report #3: Ranking Top/Least Performing MDAs
     */
    public function getRankingReport($quarter, $limit = null, $direction = 'desc')
    {
        // 1. Setup Cumulative Dates
        $settings = \App\Models\Setting::first();
        $year = $settings->fiscal_year ?? date('Y');
        
        // Change this in your BudgetPerformanceService.php
        $periods = [
            1 => ["$year-01-01", "$year-03-31"], // Jan to March
            2 => ["$year-04-01", "$year-06-30"], // April to June (STRICT)
            3 => ["$year-07-01", "$year-09-30"], // July to Sept (STRICT)
            4 => ["$year-10-01", "$year-12-31"], // Oct to Dec (STRICT)
        ];

        $start = $periods[$quarter][0];
        $end   = $periods[$quarter][1];

        // 2. Fetch MDAs with their code and spending
        return \App\Models\Mda::select('id', 'name', 'mda_code')
            ->with(['subheads.category', 'subheads.releases' => function($q) use ($start, $end) {
                $q->whereBetween('release_date', [$start, $end]);
            }])->get()->map(function ($mda) {
                
                $mdaTotals = [
                    'mda_name'    => $mda->name,
                    'mda_code'    => $mda->mda_code,
                    'revenue'     => 0,
                    'personnel'   => 0,
                    'overhead'    => 0,
                    'capital'     => 0,
                    'other'       => 0,
                    'total_spend' => 0, // This will be our sorting key
                ];

                foreach ($mda->subheads as $subhead) {
                    $actual = (float)$subhead->releases->sum('amount');
                    
                    // Categorize the spending based on Category Type
                    $type = strtoupper($subhead->category->type ?? '');

                    if (str_contains($type, 'REVENUE')) {
                        $mdaTotals['revenue'] += $actual;
                    } elseif (str_contains($type, 'PERSONNEL')) {
                        $mdaTotals['personnel'] += $actual;
                    } elseif (str_contains($type, 'OVERHEAD') || str_contains($type, 'RECURRENT')) {
                        $mdaTotals['overhead'] += $actual;
                    } elseif (str_contains($type, 'CAPITAL')) {
                        $mdaTotals['capital'] += $actual;
                    } else {
                        $mdaTotals['other'] += $actual;
                    }
                }

                // Calculate the Total Spending across all headings
                $mdaTotals['total_spend'] = $mdaTotals['revenue'] + 
                                            $mdaTotals['personnel'] + 
                                            $mdaTotals['overhead'] + 
                                            $mdaTotals['capital'] + 
                                            $mdaTotals['other'];
                
                return $mdaTotals;
            })
            // 3. Sort by Total Spend instead of Percentage
            ->sortBy([['total_spend', $direction]])
            ->values()
            ->toArray();
    }

}