<?php

namespace App\Livewire\Officer;

use App\Models\Mda;
use Livewire\Component;

class Dashboard extends Component
{
    public $mda;

    public function mount()
    {
        // Get the MDA assigned to this logged-in Budget Officer
        $this->mda = Mda::where('user_id', auth()->id())->first();
    }

    public function render()
    {
        return view('livewire.officer.dashboard');
    }
}