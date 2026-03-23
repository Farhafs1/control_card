<?php

namespace App\Livewire\Admin;

use App\Models\Release;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ExpenditureTracking extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $minAmount = '';
    public $status = 'all';
    public $perPage = 10; 

    // Properties for the Edit Modal
    public $showEditModal = false;
    public $editingReleaseId;
    public $edit_amount, $edit_reference_no, $edit_narration, $edit_release_date;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'status' => ['except' => 'all'],
        'perPage' => ['except' => 10],
    ];

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'dateFrom', 'dateTo', 'status', 'perPage', 'minAmount'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
        if (empty($this->dateFrom)) {
            $this->dateFrom = Carbon::today()->subMonth()->format('Y-m-d');
        }
        if (empty($this->dateTo)) {
            $this->dateTo = Carbon::today()->format('Y-m-d');
        }
    }

    /**
     * Open Modal and Load Data
     */
    public function editRelease($id)
    {
        $release = Release::findOrFail($id);
        $this->editingReleaseId = $id;
        $this->edit_amount = $release->amount;
        $this->edit_reference_no = $release->reference_no;
        $this->edit_narration = $release->narration;
        $this->edit_release_date = $release->release_date;
        
        $this->showEditModal = true;
    }

    /**
     * Save Changes from Modal
     */
    public function updateRelease()
    {
        $this->validate([
            'edit_amount' => 'required|numeric|min:0',
            'edit_reference_no' => 'required|string',
            'edit_narration' => 'required|string',
            'edit_release_date' => 'required|date',
        ]);

        $release = Release::find($this->editingReleaseId);
        if ($release) {
            $release->update([
                'amount' => $this->edit_amount,
                'reference_no' => $this->edit_reference_no,
                'narration' => $this->edit_narration,
                'release_date' => $this->edit_release_date,
            ]);

            $this->showEditModal = false;
            session()->flash('message', 'Record updated successfully.');
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'minAmount', 'status', 'perPage']);
        $this->dateFrom = Carbon::today()->subMonth()->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->resetPage();
    }

    public function deleteRelease($id)
    {
        $release = Release::find($id);
        if ($release) {
            $release->delete();
            session()->flash('message', 'Release record deleted successfully.');
        }
    }

    public function exportPDF()
    {
        return redirect()->route('admin.expenditure.pdf', [
            'search' => $this->search,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'status' => $this->status,
            'minAmount' => $this->minAmount,
        ]);
    }

    public function render()
    {
        $query = Release::with(['mda', 'subhead'])
            ->when($this->search, function($q) {
                $q->where(function($sub) {
                    $sub->where('reference_no', 'like', '%' . $this->search . '%')
                        ->orWhere('mda_code', 'like', '%' . $this->search . '%')
                        ->orWhere('subhead_code', 'like', '%' . $this->search . '%')
                        ->orWhere('narration', 'like', '%' . $this->search . '%')
                        ->orWhereHas('mda', function($mdaQ) {
                            $mdaQ->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('release_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('release_date', '<=', $this->dateTo))
            ->when($this->minAmount, fn($q) => $q->where('amount', '>=', $this->minAmount))
            ->when($this->status !== 'all', function($q) {
                $q->where('is_cancelled', $this->status === 'cancelled');
            });

        $totalFilteredAmount = (clone $query)->where('is_cancelled', false)->sum('amount');

        return view('livewire.admin.expenditure-tracking', [
            'releases' => $query->latest('release_date')->paginate($this->perPage),
            'totalFilteredAmount' => $totalFilteredAmount
        ]);
    }
}