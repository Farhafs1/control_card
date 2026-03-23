<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Mda;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    use WithPagination;

    public $name, $email, $password, $staff_no, $role = 'officer';
    public $is_active = true;
    public $showForm = false;
    public $search = '';
    public $editingUserId = null;

    // Assignment Modal Properties
    public $selectedMdas = []; 
    public $viewingUserId = null; // Store ID instead of the whole Model object
    public $mdaSearch = ''; 

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'staff_no' => 'required|unique:users,staff_no,' . $this->editingUserId,
            'password' => $this->editingUserId ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:admin,officer',
        ];
    }

    // Helper to get the user being viewed
    public function getViewingUserProperty()
    {
        return $this->viewingUserId ? User::find($this->viewingUserId) : null;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'staff_no' => $this->staff_no,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::find($this->editingUserId)->update($data);
            $this->dispatch('swal:toast', 'Staff profile updated successfully');
        } else {
            User::create($data);
            $this->dispatch('swal:toast', 'Staff created successfully');
        }

        $this->reset(['name', 'email', 'password', 'staff_no', 'showForm', 'editingUserId']);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->staff_no = $user->staff_no;
        $this->role = $user->role;
        $this->showForm = true;
    }

    public function toggleStatus($userId)
    {
        $user = User::find($userId);
        $user->is_active = !$user->is_active;
        $user->save();
        $this->dispatch('swal:toast', 'Status updated');
    }

    public function openAssignmentModal($userId)
    {
        // 1. Reset Modal State first
        $this->mdaSearch = '';
        $this->selectedMdas = [];
        
        // 2. Set the ID (Stable)
        $this->viewingUserId = $userId;
        
        // 3. Fetch existing assignments (even if currently empty)
        $this->selectedMdas = Mda::where('user_id', $userId)
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        // 4. Signal the modal to open
        $this->dispatch('open-modal', 'assignment-modal');
    }

    public function saveAssignments()
    {
        if (!$this->viewingUserId) return;

        // 1. Unassign all MDAs previously held by this user
        Mda::where('user_id', $this->viewingUserId)->update(['user_id' => null]);

        // 2. Assign the new batch of selected MDAs
        if (!empty($this->selectedMdas)) {
            Mda::whereIn('id', $this->selectedMdas)->update([
                'user_id' => $this->viewingUserId
            ]);
        }

        $this->dispatch('swal:toast', 'Assignments updated successfully');
        $this->dispatch('close-modal', 'assignment-modal');
        $this->reset(['selectedMdas', 'viewingUserId', 'mdaSearch']);
    }

    public function render()
    {
        return view('livewire.admin.user-management', [
            'users' => User::where(function($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('staff_no', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10),
            // Pass the viewingUser explicitly to the view
            'viewingUser' => $this->viewingUser 
        ]);
    }
}