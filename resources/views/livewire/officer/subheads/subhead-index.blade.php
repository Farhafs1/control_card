<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="serif text-4xl text-slate-900 tracking-tight">Assigned Subheads</h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-full uppercase tracking-widest">Officer View</span>
                <p class="text-[12px] font-black text-slate-500 uppercase tracking-widest">Managing Your Portfolio</p>
            </div>
        </div>

        <div class="relative w-full md:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-emerald-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                placeholder="Search your assigned MDAs..." 
                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm placeholder:text-slate-400 font-medium"
            >
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="overflow-y-auto max-h-[70vh] custom-scrollbar"> 
            <table class="w-full text-left border-separate border-spacing-0 relative z-0">
                <thead class="relative z-50">
                    <tr>
                        <th class="sticky top-0 z-50 px-8 py-5 text-sm font-black uppercase tracking-widest border-b border-slate-100 bg-slate-50/95 backdrop-blur-md">MDA Code</th>
                        <th class="sticky top-0 z-50 px-8 py-5 text-sm font-black uppercase tracking-widest border-b border-slate-100 bg-slate-50/95 backdrop-blur-md">Organization Name</th>
                        <th class="sticky top-0 z-50 px-8 py-5 text-sm font-black uppercase tracking-widest text-right border-b border-slate-100 bg-slate-50/95 backdrop-blur-md">Total Provision</th>
                        <th class="sticky top-0 z-50 px-8 py-5 text-sm font-black uppercase tracking-widest text-center border-b border-slate-100 bg-slate-50/95 backdrop-blur-md">Action</th>
                    </tr>
                </thead>
                <tbody class="relative z-10 divide-y divide-slate-50">
                    @forelse($mdas as $mda)
                    <tr class="hover:bg-emerald-50/30 transition-colors group">
                        <td class="px-8 py-5 font-mono text-m text-slate-500">{{ $mda->mda_code }}</td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 uppercase tracking-tight group-hover:text-emerald-900 transition-colors">{{ $mda->name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right font-bold text-slate-900 text-m">
                            {{-- Corrected to use total_provision from your withSum() query --}}
                            <span class="font-mono">₦{{ number_format($mda->total_provision ?? 0, 2) }}</span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <button wire:click="selectMda({{ $mda->id }})" class="isolate inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-black uppercase tracking-tighter hover:bg-emerald-700 transition-all transform hover:-translate-y-0.5 active:translate-y-0 shadow-md shadow-slate-200">
                                View Subheads
                                <svg class="ml-2 w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-slate-50 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <p class="text-slate-500 font-medium">You have no MDAs assigned to your account.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-6">
        {{ $mdas->links() }}
    </div>
</div>