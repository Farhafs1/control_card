<?php

namespace App\Livewire\Admin;

use App\Models\Release;
use Livewire\Component;

class ExpenditureEdit extends Component
{
    public Release $release;
    
    // Form fields
    public $amount;
    public $reference_no;
    public $narration;
    public $release_date;

    public function mount(Release $release)
    {
        $this->release = $release;
        $this->amount = $release->amount;
        $this->reference_no = $release->reference_no;
        $this->narration = $release->narration;
        $this->release_date = $release->release_date;
    }

    public function update()
    {
        $this->validate([
            'amount' => 'required|numeric',
            'reference_no' => 'required',
            'narration' => 'required',
            'release_date' => 'required|date',
        ]);

        $this->release->update([
            'amount' => $this->amount,
            'reference_no' => $this->reference_no,
            'narration' => $this->narration,
            'release_date' => $this->release_date,
        ]);

        session()->flash('message', 'Release record updated successfully.');
        return redirect()->route('admin.expenditure');
    }

    public function render()
    {
        return view('livewire.admin.expenditure-edit')->layout('layouts.app');
    }
}