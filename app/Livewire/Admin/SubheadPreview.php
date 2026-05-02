<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Subhead;
use Livewire\WithPagination;
use Illuminate\Database\QueryException;

class SubheadPreview extends Component
{
    use WithPagination;

    public $search = '';
    public $mdaSearch = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingMdaSearch() { $this->resetPage(); }

    /**
     * Inline update logic for the Excel-like grid
     */
    public function updateValue($id, $field, $value)
    {
        $subhead = Subhead::find($id);
        
        // Define all fields that are numeric/monetary
        $numericFields = [
            'approved_provision', 
            'additional_provision', 
            'virement', 
            'supplementary'
        ];

        // Clean numeric values (strip commas) if the field is in our list
        $cleanValue = in_array($field, $numericFields) 
            ? str_replace(',', '', $value) 
            : $value;
        
        if ($subhead) {
            try {
                $subhead->update([
                    $field => $cleanValue
                ]);
                
                session()->flash('message', 'Record updated successfully!');
            } catch (QueryException $e) {
                // Handle duplicate entry errors (SQLSTATE 23000)
                if ($e->getCode() == 23000) {
                    session()->flash('error', 'Update Failed: This Code/Description already exists for this MDA.');
                } else {
                    session()->flash('error', 'Database error: Could not save changes.');
                }
            }
        }
    }

    /**
     * Delete a budget line
     */
    public function deleteSubhead($id)
    {
        Subhead::find($id)->delete();
        session()->flash('message', 'Subhead removed from provisions.');
    }

    public function render()
    {
        $query = Subhead::with('mda');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('subhead_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->mdaSearch)) {
            $query->whereHas('mda', function($q) {
                $q->where('name', 'like', '%' . $this->mdaSearch . '%');
            });
        }

        return view('livewire.admin.subhead-preview', [
            'subheads' => $query->latest()->paginate(50)
        ]);
    }
}