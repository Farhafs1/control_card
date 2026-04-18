<div class="max-w-4xl mx-auto p-6 space-y-10">
    {{-- Header --}}
    <div>
        <h2 class="serif text-4xl text-slate-900 tracking-tight">Account Settings</h2>
        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.3em] mt-2">Manage your administrative credentials</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- Left Column: Info --}}
        <div class="md:col-span-1">
            <h3 class="text-sm font-bold text-slate-800">Public Profile</h3>
            <p class="text-xs text-slate-500 mt-1">This information will be displayed on your budget reports and audit logs.</p>
        </div>

        {{-- Right Column: Profile Form --}}
        <div class="md:col-span-2 bg-white rounded-[2rem] border border-slate-100 shadow-xl p-8">
            <form wire:submit="updateProfile" class="space-y-6">
                @if (session()->has('profile_success'))
                    <div class="p-4 mb-4 text-sm text-emerald-700 bg-emerald-50 rounded-xl border border-emerald-100">
                        {{ session('profile_success') }}
                    </div>
                @endif

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Display Name</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm">
                    @error('name') <span class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                    <input type="email" wire:model="email" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm">
                    @error('email') <span class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-emerald-600 transition-all shadow-lg shadow-slate-200">
                        Update Info
                    </button>
                </div>
            </form>
        </div>
    </div>

    <hr class="border-slate-100">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- Left Column: Security Info --}}
        <div class="md:col-span-1">
            <h3 class="text-sm font-bold text-slate-800">Security & Privacy</h3>
            <p class="text-xs text-slate-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
        </div>

        {{-- Right Column: Password Form --}}
        <div class="md:col-span-2 bg-white rounded-[2rem] border border-slate-100 shadow-xl p-8">
            <form wire:submit="updatePassword" class="space-y-6">
                @if (session()->has('password_success'))
                    <div class="p-4 mb-4 text-sm text-emerald-700 bg-emerald-50 rounded-xl border border-emerald-100">
                        {{ session('password_success') }}
                    </div>
                @endif

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current Password</label>
                    <input type="password" wire:model="current_password" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm">
                    @error('current_password') <span class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">New Password</label>
                        <input type="password" wire:model="new_password" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm">
                        @error('new_password') <span class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Confirm New Password</label>
                        <input type="password" wire:model="new_password_confirmation" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm">
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-8 py-3 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition-all shadow-lg shadow-emerald-100">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>