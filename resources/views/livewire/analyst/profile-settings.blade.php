<div class="max-w-4xl mx-auto p-6 space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('profile_success') || session()->has('password_success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" 
             class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm">
            {{ session('profile_success') ?? session('password_success') }}
        </div>
    @endif

    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
        <div class="p-8 border-b border-slate-50">
            <h2 class="text-2xl font-bold text-slate-800">Profile Settings</h2>
            <p class="text-slate-500 text-sm mt-1">Manage your account information and security preferences.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 p-8">
            {{-- Profile Information --}}
            <form wire:submit.prevent="updateProfile" class="space-y-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Information</h3>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                    <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl hover:bg-emerald-600 transition-colors font-medium">
                    Save Profile
                </button>
            </form>

            {{-- Change Password --}}
            <form wire:submit.prevent="updatePassword" class="space-y-6 border-t md:border-t-0 md:border-l border-slate-100 pt-8 md:pt-0 md:pl-8">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Security</h3>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Current Password</label>
                    <input type="password" wire:model="current_password" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">New Password</label>
                    <input type="password" wire:model="new_password" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirm Password</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                </div>

                <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-xl hover:bg-emerald-700 transition-colors font-medium">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>