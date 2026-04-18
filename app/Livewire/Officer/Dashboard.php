<?php

namespace App\Livewire\Officer;

use App\Models\Mda;
use App\Models\PendingVerification;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        // 1. Fetch all MDAs assigned to this officer with their relationships
        $mdas = Mda::with(['subheads', 'releases'])
            ->where('user_id', auth()->id())
            ->get();

        // 2. Calculate global stats for this officer's portfolio
        $stats = [
            'total_allocation' => $mdas->flatMap->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision),
            'total_spent' => $mdas->flatMap->releases->where('is_cancelled', false)->sum('amount'),
            'pending_count' => PendingVerification::whereIn('mda_id', $mdas->pluck('id'))->count(),
        ];

        // 3. Identify Subheads with less than 10% funds remaining
        $criticalSubheads = $mdas->flatMap->subheads->filter(function($subhead) {
            $spent = $subhead->releases->where('is_cancelled', false)->sum('amount');
            $balance = $subhead->total_budget - $spent;
            return $subhead->total_budget > 0 && ($balance / $subhead->total_budget) < 0.10;
        })->take(5);

        return view('livewire.officer.dashboard', [
            'mdas' => $mdas,
            'stats' => $stats,
            'criticalSubheads' => $criticalSubheads
        ]);
    }

    
}