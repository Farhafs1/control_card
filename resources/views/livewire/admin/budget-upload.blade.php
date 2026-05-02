<div class="space-y-6">
    <div class="flex justify-between items-end bg-slate-900 p-8 rounded-[2rem] shadow-2xl border-b-4 border-emerald-500">
        <div>
            <h2 class="text-3xl font-black text-white tracking-tight uppercase">Budget Master</h2>
            <p class="text-emerald-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-1">System-Wide Subhead Synchronization</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subhead-preview') }}" 
            class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 group {{ request()->routeIs('admin.subhead-preview') ? 'bg-emerald-900/50 text-white' : 'text-emerald-100 hover:bg-emerald-600/50' }}">
                
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('admin.subhead-preview') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}" 
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>

                <span class="font-medium text-sm">Budget Previewer & Editor</span>
            </a>
            <button wire:click="exportBudget" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition shadow-lg shadow-emerald-900/20">
                📥 Backup Data
            </button>
            <button wire:click="downloadTemplate" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition border border-slate-700">
                CSV Template
            </button>
            <button onclick="confirmTruncate()" class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition border border-red-500/20">
                Truncate System
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-200">
                <form wire:submit.prevent="save" class="space-y-8">
                    <div class="relative group">
                        <input type="file" wire:model="budget_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="border-4 border-dashed border-slate-100 rounded-[2rem] p-16 text-center group-hover:border-emerald-400 group-hover:bg-emerald-50/30 transition-all duration-300">
                            <div class="w-20 h-20 bg-emerald-100 rounded-3xl flex items-center justify-center mx-auto mb-6 text-emerald-600 group-hover:scale-110 transition duration-500">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            </div>
                            <p class="text-lg font-black text-slate-700">
                                {{ $budget_file ? $budget_file->getClientOriginalName() : 'Drop Global Budget CSV' }}
                            </p>
                            <p class="text-xs text-slate-400 uppercase mt-2 font-bold tracking-widest">Supports .csv files up to 20MB</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Action Status</span>
                            <div wire:loading wire:target="budget_file" class="text-xs font-bold text-emerald-600 animate-pulse uppercase">File Uploading...</div>
                            <div wire:loading.remove wire:target="budget_file" class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Ready for processing</div>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" class="bg-emerald-900 text-white px-12 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-800 transition shadow-2xl shadow-emerald-900/40 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Synchronize Budget</span>
                            <span wire:loading wire:target="save">Synchronizing...</span>
                        </button>
                    </div>
                </form>
            </div>

            @if(session()->has('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-2xl">
                    <p class="text-xs font-bold text-red-700 uppercase">{{ session('error') }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6 border-b border-slate-50 pb-4">Live Statistics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Total Subheads</span>
                        <span class="text-sm font-black text-slate-800">{{ number_format($stats['total_subheads']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Active MDAs</span>
                        <span class="text-sm font-black text-emerald-600">{{ $stats['total_mdas'] }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-50">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Last System Sync</span>
                        <span class="text-[10px] font-black text-slate-600 uppercase">{{ $stats['last_entry'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-emerald-900 rounded-[2rem] p-8 text-white shadow-xl shadow-emerald-900/20">
                <h3 class="text-xs font-black text-emerald-400 uppercase tracking-widest mb-4">Maintenance Protocol</h3>
                <p class="text-[10px] font-bold text-emerald-100 uppercase leading-relaxed opacity-90">
                    The system validates uniqueness based on the <span class="text-white underline">MDA + Subhead Code</span> combination.
                </p>
                <p class="text-[9px] text-emerald-300/60 uppercase mt-4 font-bold leading-tight">
                    Common codes (e.g., Salary 21010101) are handled as unique entries for each Ministry. Updating a row in the CSV will refresh the existing budget line without creating duplicates.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmTruncate() {
        if (confirm("🚨 DANGER: You are about to wipe the entire 2026 Budget database. This action is irreversible. Proceed?")) {
            @this.truncateBudget();
        }
    }
</script>