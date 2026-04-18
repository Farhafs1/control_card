<?php

namespace App\Livewire\Officer;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfileSettings extends Component
{
    public $name;
    public $email;
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        session()->flash('profile_success', 'Profile details updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password_success', 'Password changed successfully.');
    }

    public function render()
    {
        return view('livewire.officer.profile-settings')->layout('layouts.app');
    }
}