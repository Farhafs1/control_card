<div class="max-w-6xl mx-auto py-10 px-4">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">Admin Control Center</h1>
        <p class="text-slate-500 text-sm">Configure your profile and global system parameters.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- LEFT COLUMN: Profile & Identity --}}
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center">
                    <i class="fas fa-user-circle mr-2 text-blue-500"></i> Administrative Profile
                </h3>
                
                <form wire:submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Full Name</label>
                        <input type="text" wire:model="name" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Email Address</label>
                        <input type="email" wire:model="email" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <hr class="border-slate-100">
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">New Password (Optional)</label>
                        <input type="password" wire:model="password" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    </div>
                    <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-xl font-bold hover:bg-slate-700 transition">Save Profile</button>
                </form>
            </div>

            {{-- Visual Branding Preview --}}
            <div class="bg-slate-900 p-6 rounded-2xl shadow-lg text-white">
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold mb-4">Branding Preview</p>
                <div class="flex items-center space-x-4">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="h-12 w-12 object-contain rounded">
                    @elseif($existing_logo_path)
                        <img src="{{ Storage::url($existing_logo_path) }}" class="h-12 w-12 object-contain rounded">
                    @else
                        <div class="h-12 w-12 bg-slate-700 rounded flex items-center justify-center italic text-xs">No Logo</div>
                    @endif
                    <div>
                        <h4 class="font-bold leading-none">{{ $app_name }}</h4>
                        <p class="text-xs text-slate-400 mt-1">{{ $state_name ?? 'Location Not Set' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Global System Setup --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-700 mb-6 flex items-center">
                    <i class="fas fa-cogs mr-2 text-emerald-500"></i> System Configuration
                </h3>

                <form wire:submit.prevent="updateSystemSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Fiscal Setup --}}
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Active Fiscal Year</label>
                        <select wire:model="fiscal_year" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Budget Status</label>
                        <select wire:model="budget_status" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                            <option value="active">Active (Processing Releases)</option>
                            <option value="provisional">Provisional (Limited Access)</option>
                            <option value="closed">Closed (Auditing Only)</option>
                        </select>
                    </div>

                    {{-- Branding Setup --}}
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-500 uppercase">Government Institution / State Name</label>
                        <input type="text" wire:model="state_name" placeholder="e.g. Katsina State Government" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Application Name</label>
                        <input type="text" wire:model="app_name" class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase">Update Logo</label>
                        <input type="file" wire:model="logo" class="w-full mt-1 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    {{-- Policy Toggles --}}
                    <div class="md:col-span-2 p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-700">Allow Overspending</h4>
                            <p class="text-[10px] text-slate-500">If enabled, releases can exceed the allocated subhead budget.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="allow_overspending" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <div class="md:col-span-2 flex justify-end pt-4">
                        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all">
                            Apply Global Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>