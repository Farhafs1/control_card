<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    // Admin Profile Fields
    public $name, $email, $password, $password_confirmation;

    // System Setting Fields
    public $fiscal_year, $budget_status, $app_name, $state_name, $currency_symbol, $allow_overspending;
    public $opening_balance, $expected_revenue; // New Fields added
    public $logo, $existing_logo_path;

    public function mount()
    {
        // Load User Data
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;

        // Load System Settings via the Model helper we updated earlier
        $settings = Setting::current();

        $this->fiscal_year = $settings->fiscal_year;
        $this->opening_balance = $settings->opening_balance;
        $this->expected_revenue = $settings->expected_revenue;
        $this->budget_status = $settings->budget_status;
        $this->app_name = $settings->app_name;
        $this->state_name = $settings->state_name;
        $this->currency_symbol = $settings->currency_symbol;
        $this->allow_overspending = (bool)$settings->allow_overspending;
        $this->existing_logo_path = $settings->logo_path;
    }

    public function updateProfile()
    {
        $user = auth()->user();
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|confirmed|min:8',
        ]);

        if ($this->password) {
            $user->password = Hash::make($this->password);
        }
        
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        $this->dispatch('notify', ['message' => 'Profile updated successfully!', 'type' => 'success']);
    }

    public function updateSystemSettings()
    {
        $this->validate([
            'fiscal_year' => 'required|integer',
            'opening_balance' => 'required', // numeric validation handled after cleaning
            'expected_revenue' => 'required',
            'budget_status' => 'required',
            'app_name' => 'required|string',
            'logo' => 'nullable|image|max:1024', 
        ]);

        $settings = Setting::first();
        
        // Clean numeric inputs (remove commas if entered by user)
        $cleanOpeningBalance = str_replace(',', '', $this->opening_balance);
        $cleanExpectedRevenue = str_replace(',', '', $this->expected_revenue);

        if ($this->logo) {
            if ($settings->logo_path) Storage::delete($settings->logo_path);
            $this->existing_logo_path = $this->logo->store('branding', 'public');
        }

        $settings->update([
            'fiscal_year' => $this->fiscal_year,
            'opening_balance' => $cleanOpeningBalance,
            'expected_revenue' => $cleanExpectedRevenue,
            'budget_status' => $this->budget_status,
            'app_name' => $this->app_name,
            'state_name' => $this->state_name,
            'currency_symbol' => $this->currency_symbol,
            'allow_overspending' => $this->allow_overspending,
            'logo_path' => $this->existing_logo_path,
        ]);

        $this->dispatch('notify', ['message' => 'System settings applied!', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.app');
    }
}