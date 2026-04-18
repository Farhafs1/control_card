<?php
namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class SystemLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $filterModule = '';

    public function render()
    {
        $logs = ActivityLog::with('user')
            ->when($this->search, function($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filterModule, fn($q) => $q->where('module', $this->filterModule))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.system-logs', [
            'logs' => $logs
        ]);
    }
}