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
        // General Totals
        $totalBudget = Subhead::sum(DB::raw('approved_provision + additional_provision'));
        
        // Categorical Breakdown logic
        $categories = ['Revenue', 'Personnel', 'Overhead', 'Capital'];
        $breakdown = [];
        
        foreach ($categories as $cat) {
            $breakdown[strtolower($cat)] = Subhead::whereHas('category', function($q) use ($cat) {
                $q->where('type', $cat);
            })->sum(DB::raw('approved_provision + additional_provision'));
        }

        return view('livewire.admin.dashboard', [
            'totalBudget' => $totalBudget,
            'totalMdas' => Mda::count(),
            'activeMdas' => Mda::has('subheads')->count(),
            'totalStaff' => User::where('role', 'officer')->count(),
            'breakdown' => $breakdown,
            'recentActivity' => Subhead::with('mda')->latest()->take(6)->get()
        ]);
    }
}