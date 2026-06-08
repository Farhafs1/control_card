<?php

namespace App\Livewire\Admin;

use App\Models\Mda;
use App\Models\User;
use App\Models\Subhead;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        // 1. Calculate Revenue (Inflow)
        $totalRevenue = Subhead::whereHas('category', function($q) {
            $q->where('type', 'Revenue');
        })->sum(DB::raw('approved_provision + additional_provision'));

        // 2. Calculate Expenditure (Outflow) - Everything except Revenue
        $totalExpenditure = Subhead::whereHas('category', function($q) {
            $q->where('type', '<>', 'Revenue');
        })->sum(DB::raw('approved_provision + additional_provision'));

        // Keep categorical breakdown for the chart
        $categories = ['Revenue', 'Personnel', 'Overhead', 'Capital'];
        $breakdown = [];
        foreach ($categories as $cat) {
            $breakdown[strtolower($cat)] = Subhead::whereHas('category', function($q) use ($cat) {
                $q->where('type', $cat);
            })->sum(DB::raw('approved_provision + additional_provision'));
        }

        return view('livewire.admin.dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalExpenditure' => $totalExpenditure,
            'breakdown' => $breakdown,
            'totalMdas' => Mda::count(),
            'activeMdas' => Mda::has('subheads')->count(),
            'totalStaff' => User::where('role', 'officer')->count(),
            'recentActivity' => Subhead::with('mda')->latest()->take(6)->get()
        ]);
    }
}