<?php

namespace App\Livewire\Analyst;

use App\Models\Mda;
use App\Models\Subhead;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function mount()
    {
        // Ensure only authorized roles access this
        if (!Auth::user()->isAnalyst() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        // 1. Revenue (Inflow)
        $revenueTotal = Subhead::whereHas('category', fn($q) => $q->where('type', 'Revenue'))
                                ->sum(DB::raw('approved_provision + additional_provision'));

        $actualRevenue = \App\Models\Release::whereHas('subhead.category', fn($q) => $q->where('type', 'Revenue'))
                                            ->sum('amount');
        
        $revenueBalance = $revenueTotal - $actualRevenue;
        $inflowRate = $revenueTotal > 0 ? ($actualRevenue / $revenueTotal) * 100 : 0;

        // 2. Expenditure (Outflow)
        $spendingCategories = ['Personnel', 'Overhead', 'Capital'];
        
        $totalProvision = Subhead::whereHas('category', fn($q) => $q->whereIn('type', $spendingCategories))
                                    ->sum(DB::raw('approved_provision + additional_provision'));
        
        $totalReleased = \App\Models\Release::whereHas('subhead.category', fn($q) => $q->whereIn('type', $spendingCategories))
                                            ->sum('amount');
        
        $utilizationPercent = $totalProvision > 0 ? ($totalReleased / $totalProvision) * 100 : 0;
        $varianceAmount = $totalProvision - $totalReleased;

        // 3. Efficiency Metrics & Chart Data
        $allCategories = ['Personnel', 'Overhead', 'Capital', 'Revenue'];
        $fiscalPerformance = [];
        
        foreach ($allCategories as $cat) {
            $fiscalPerformance[$cat] = [
                'budgeted' => Subhead::whereHas('category', fn($q) => $q->where('type', $cat))
                                    ->sum(DB::raw('approved_provision + additional_provision')),
                'released' => \App\Models\Release::whereHas('subhead.category', fn($q) => $q->where('type', $cat))
                                    ->sum('amount')
            ];
        }

        return view('livewire.analyst.dashboard', [
            // Revenue (Inflow)
            'revenueTotal'        => $revenueTotal,
            'actualRevenue'       => $actualRevenue,
            'revenueBalance'      => $revenueBalance,
            'inflow_rate'         => number_format($inflowRate, 1) . '%',
            
            // Expenditure (Outflow)
            'totalProvision'      => $totalProvision,
            'totalReleased'       => $totalReleased,
            'utilization_percent' => number_format($utilizationPercent, 1) . '%',
            'variance_amount'     => $varianceAmount,
            
            // Table & Chart Data
            'fiscalPerformance'   => $fiscalPerformance,
            'chartData'           => json_encode($fiscalPerformance), // Ready for JS integration
            
            'topPerformingMdas'   => Mda::withSum(['releases' => fn($q) => $q->whereHas('subhead.category', fn($cat) => $cat->whereIn('type', $spendingCategories))], 'amount')
                                            ->orderBy('releases_sum_amount', 'desc')
                                            ->take(5)
                                            ->get(),
        ])->layout('layouts.app');
    }
}