<!-- <div class="space-y-8">
    <div class="flex items-center gap-6">
        <button wire:click="resetSelection" class="w-12 h-12 bg-white rounded-2xl border border-slate-100 shadow-sm flex items-center justify-center hover:bg-slate-50 transition-colors">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div>
            <h2 class="serif text-3xl text-slate-900 uppercase">{{ $mda->name }}</h2>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MDA Code: {{ $mda->mda_code }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-emerald-900 p-8 rounded-[2rem] text-white shadow-xl shadow-emerald-900/20">
            <p class="text-[9px] font-black text-emerald-400 uppercase tracking-[0.2em]">Selected Category Total</p>
            <h3 class="text-2xl font-black mt-2 italic serif">₦ {{ number_format($mdaCategoryTotal, 2) }}</h3>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-800 rounded text-emerald-300 uppercase tracking-tighter">{{ $activeCategory }}</span>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Fiscal Weightage</p>
            <h3 class="text-2xl font-black text-slate-900 mt-2">{{ number_format($percentage, 2) }}%</h3>
            <p class="text-[9px] text-slate-400 font-bold uppercase mt-2 italic">Of Total State {{ $activeCategory }} Provision</p>
        </div>

        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Cumulative MDA Provision</p>
            <h3 class="text-2xl font-black text-slate-900 mt-2">₦ {{ number_format($mda->subheads()->sum(DB::raw('approved_provision + additional_provision')), 2) }}</h3>
            <p class="text-[9px] text-emerald-600 font-bold uppercase mt-2 tracking-widest italic">All Categories Combined</p>
        </div>
    </div>

    <div class="flex gap-4 p-1 bg-slate-100/50 rounded-2xl w-fit border border-slate-100">
        @foreach(['Personnel', 'Overhead', 'Capital', 'Revenue'] as $cat)
        <button wire:click="$set('activeCategory', '{{ $cat }}')" 
            class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeCategory == $cat ? 'bg-white text-emerald-900 shadow-sm scale-105' : 'text-slate-400 hover:text-slate-600' }}">
            {{ $cat }}
        </button>
        @endforeach
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest w-40">Code</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Subhead Description</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Approved</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Additional</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($subheads as $sh)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    {{-- FIXED: Matches Migration 'subhead_code' --}}
                    <td class="px-8 py-5 font-mono text-xs text-slate-500">{{ $sh->subhead_code }}</td>
                    
                    {{-- FIXED: Matches Migration 'description' --}}
                    <td class="px-8 py-5 text-sm font-bold text-slate-800 uppercase tracking-tight">{{ $sh->description }}</td>
                    
                    <td class="px-8 py-5 text-right text-sm text-slate-600 font-mono">₦ {{ number_format($sh->approved_provision, 2) }}</td>
                    <td class="px-8 py-5 text-right text-sm text-emerald-600 font-mono">+ ₦ {{ number_format($sh->additional_provision, 2) }}</td>
                    
                    {{-- FIXED: Matches Model Attribute 'total_budget' --}}
                    <td class="px-8 py-5 text-right text-sm font-black text-slate-900 font-mono">₦ {{ number_format($sh->total_budget, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div> -->