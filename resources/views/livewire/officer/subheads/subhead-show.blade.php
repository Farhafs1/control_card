<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-4 mb-2">
                {{-- Back to Officer Subheads List --}}
                <a href="{{ route('officer.subheads') }}" 
                   wire:navigate
                   class="group flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 shadow-sm hover:border-emerald-200 hover:bg-emerald-50 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <ol class="inline-flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest">
                    <li><a href="{{ route('officer.subheads') }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 transition-colors">My MDAs</a></li>
                    <li class="text-slate-300">/</li>
                    <li class="text-slate-500">{{ $mda->mda_code }}</li>
                </ol>
            </nav>
            <h2 class="serif text-4xl text-slate-900 tracking-tight">{{ $mda->name }}</h2>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Budget Allocation Details</p>
        </div>
        
        {{-- Total MDA Provision Badge --}}
        <div class="bg-slate-900 px-16 py-4 rounded-3xl shadow-xl shadow-slate-200">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Assigned Portfolio Total</p>
            <p class="text-2xl font-mono font-bold text-white">₦{{ number_format($mdaTotal, 2) }}</p>
        </div>
    </div>

    {{-- Integrated Table Section --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        
        {{-- Fused Category Tabs --}}
        <div class="flex border-b border-slate-50 bg-slate-50/50 p-2 gap-1">
            @foreach($categoryTotals as $name => $total)
                <button 
                    wire:click="setCategory('{{ $name }}')"
                    class="flex-1 px-4 py-3 rounded-2xl transition-all text-left group
                    {{ $activeCategory === $name 
                        ? 'bg-white shadow-sm ring-1 ring-slate-200/50' 
                        : 'hover:bg-white/50' 
                    }}"
                >
                    <p class="text-[14px] font-black uppercase tracking-tighter {{ $activeCategory === $name ? 'text-emerald-600' : 'text-slate-600' }}">
                        @if($name === 'Personnel') Personnel Cost 
                        @elseif($name === 'Overhead') Overhead Cost
                        @elseif($name === 'Capital') Capital Expenditure
                        @else {{ $name }} @endif
                    </p>
                    <p class="text-m font-bold {{ $activeCategory === $name ? 'text-slate-900' : 'text-slate-600' }}">
                        ₦{{ number_format($total, 2) }}
                    </p>
                </button>
            @endforeach
        </div>

        {{-- Table Container with Sticky Header --}}
        <div class="overflow-y-auto max-h-[60vh] custom-scrollbar relative isolate"> 
            <table class="w-full text-left border-separate border-spacing-0 relative z-0">
                <thead class="sticky top-0 z-50">
                    <tr>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest border-b border-slate-100 bg-slate-50">Code</th>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest border-b border-slate-100 bg-slate-50">Subhead Description</th>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest text-right border-b border-slate-100 bg-slate-50">Approved</th>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest text-right border-b border-slate-100 bg-slate-50">Additional</th>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest text-right border-b border-slate-100 bg-slate-50">Total</th>
                        <th class="px-8 py-5 text-sm font-black text-slate-700 uppercase tracking-widest text-center border-b border-slate-100 bg-slate-50">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($subheads as $subhead)
                        <tr class="hover:bg-emerald-50/20 transition-colors group">
                            <td class="px-8 py-5 font-mono font-bold text-m text-slate-700">{{ $subhead->subhead_code }}</td>
                            <td class="px-8 py-5 text-sm font-bold text-slate-800 uppercase tracking-tight">{{ $subhead->description }}</td>
                            <td class="px-8 py-5 text-right font-mono font-bold text-m text-slate-700">
                                {{ number_format($subhead->approved_provision, 2) }}
                            </td>
                            <td class="px-8 py-5 text-right font-mono text-m text-emerald-700">
                                + {{ number_format($subhead->additional_provision, 2) }}
                            </td>
                            <td class="px-8 py-5 text-right font-bold text-slate-900 text-m">
                                ₦{{ number_format($subhead->total_budget, 2) }}
                            </td>
                            <td class="px-8 py-5 text-center">
                                {{-- Link to the Officer version of the Bin Card --}}
                                <a href="{{ route('officer.subheads.bin-card', $subhead->id) }}" 
                                   wire:navigate
                                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-100 text-[14px] font-black uppercase tracking-widest text-slate-800 hover:bg-emerald-900 hover:text-white transition-all duration-300 shadow-sm"
                                   title="Open Control Card">
                                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Manage Card
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <div class="p-4 bg-slate-50 rounded-full mb-4">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">No Records Found</p>
                                    <p class="text-slate-400 text-sm mt-1 italic">
                                        No subheads matched the category "{{ $activeCategory }}".
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>