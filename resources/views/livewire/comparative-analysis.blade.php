<div class="space-y-6 max-w-[1600px] mx-auto p-6 bg-slate-50 min-h-screen">
    
    <!-- Top Control Bar: Global Filters -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Comparative Analytics</h1>
            <p class="text-sm text-slate-500 font-medium">Cross-entity & multi-period variance tracking</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
             <!-- Category Filter Dropdown -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl mr-4 border border-slate-200">
                <label for="categoryFilter" class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Filter</label>
                <select 
                    wire:model.live="filter" 
                    id="categoryFilter"
                    class="bg-white border-none text-xs font-bold text-indigo-600 rounded-lg py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500 shadow-sm cursor-pointer transition-all"
                >
                    <option value="all">ALL CATEGORIES</option>
                    <option disabled>──────────</option>
                    <option value="all_expenditure">ALL EXPENDITURE</option>
                    <option value="Revenue">REVENUE ONLY</option>
                    <option disabled>──────────</option>
                    <option value="Personnel">PERSONNEL</option>
                    <option value="Overhead">OVERHEAD</option>
                    <option value="Capital">CAPITAL</option>
                </select>
            </div>

             <!-- AI Insight Trigger with Loading State -->
             <button wire:click="generateAiReport" 
                     wire:loading.attr="disabled"
                     class="relative flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-200 disabled:opacity-70">
                
                <div wire:loading wire:target="generateAiReport" class="absolute inset-0 flex items-center justify-center bg-indigo-600 rounded-xl">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <svg wire:loading.remove wire:target="generateAiReport" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span wire:loading.remove wire:target="generateAiReport">GENERATE AI INSIGHTS</span>
                <span wire:loading wire:target="generateAiReport">CONSULTING AI...</span>
            </button>
        </div>
    </div>

    <!-- Comparison Matrix Configurator -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach($comparisonPeriods as $index => $period)
            <div class="bg-white p-5 rounded-3xl border-2 {{ $loop->last ? 'border-indigo-500 shadow-indigo-100 shadow-lg' : 'border-slate-100' }} shadow-sm relative transition-all duration-500">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest {{ $loop->last ? 'text-indigo-500' : 'text-slate-400' }}">
                        Dataset {{ chr(65 + $index) }}
                    </span>
                    @if(count($comparisonPeriods) > 1)
                        <button wire:click="removePeriod({{ $index }})" class="text-slate-300 hover:text-rose-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2"/></svg>
                        </button>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <select wire:model.live="comparisonPeriods.{{ $index }}.year" class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold py-3 px-4 outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="2024">Fiscal Year 2024</option>
                        <option value="2025">Fiscal Year 2025</option>
                        <option value="2026">Fiscal Year 2026</option>
                    </select>
                    <select wire:model.live="comparisonPeriods.{{ $index }}.quarter" class="w-full bg-slate-50 border-none rounded-xl text-xs font-bold py-3 px-4 outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="all">Full Year (Total)</option>
                        <option value="1">Q1: Jan - Mar</option>
                        <option value="2">Q2: Apr - Jun</option>
                        <option value="3">Q3: Jul - Sep</option>
                        <option value="4">Q4: Oct - Dec</option>
                    </select>
                </div>
            </div>
        @endforeach

        @if(count($comparisonPeriods) < 4)
            <button wire:click="addPeriod" class="border-2 border-dashed border-slate-200 rounded-3xl flex flex-col items-center justify-center text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50/30 transition-all group min-h-[120px]">
                <div class="p-3 rounded-full bg-slate-50 group-hover:bg-indigo-100 transition-colors mb-2">
                    <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round"/></svg>
                </div>
                <span class="text-xs font-black uppercase tracking-tighter">Add Period</span>
            </button>
        @endif
    </div>

    <!-- AI Insights Panel -->
    <!-- AI Insights Panel (Clean & Solid Version) -->
    @if($aiInsight)
    <div class="bg-white border-[6px] border-[#BDB76B] rounded-[2.5rem] shadow-xl relative overflow-hidden animate-in fade-in zoom-in duration-500">
        
        <!-- Top Header Bar inside the panel -->
        <div class="bg-white border-b border-slate-100 px-8 py-6">
            <div class="flex items-center gap-3">
                <span class="px-4 py-1.5 bg-[#BDB76B]/10 rounded-full text-[10px] font-black tracking-widest text-[#BDB76B] uppercase border border-[#BDB76B]/20">
                    Strategic Intelligence Report
                </span>
                <div class="h-1 w-12 bg-[#BDB76B] rounded-full"></div>
            </div>
        </div>

        <div class="p-10 lg:p-14">
            <h2 class="text-3xl font-black text-slate-900 mb-8 tracking-tight">AI Generated Comparative Analysis</h2>
            
            <!-- This container makes the AI text spacious and readable -->
            <article class="prose prose-slate max-w-none 
                prose-p:text-slate-700 prose-p:leading-[1.8] prose-p:text-lg prose-p:mb-8
                prose-headings:text-slate-900 prose-headings:font-black prose-headings:mt-12 prose-headings:mb-6
                prose-strong:text-slate-900 prose-strong:font-black prose-strong:underline prose-strong:decoration-[#BDB76B] prose-strong:decoration-2
                prose-ul:my-8 prose-li:text-slate-700 prose-li:mb-4">
                
                {!! str($aiInsight)->markdown() !!}
            </article>
        </div>
    </div>
    @endif

    <!-- Comparison Table -->
    {{-- Added a master key that changes based on filter and period count --}}
    <div 
        wire:key="analytics-wrapper-{{ $filter }}-{{ count($comparisonPeriods) }}" 
        class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl overflow-hidden transition-all duration-700"
    >
        {{-- Optional: Loading overlay to show user the filter is actually working --}}
        <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-10 flex items-center justify-center">
            <div class="animate-pulse text-indigo-500 font-black tracking-widest text-xs">REFRESHING DATA...</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 backdrop-blur-sm">
                        <th class="px-8 py-8 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                                <span>Entity Metadata</span>
                            </div>
                        </th>

                        <th class="px-6 py-8 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right border-l border-slate-100/50 bg-slate-100/20">
                            <div class="flex flex-col">
                                <span class="text-indigo-500 font-bold">{{ $this->currentYear }}</span>
                                <span class="text-slate-600">Approved Provision</span>
                            </div>
                        </th>

                        @foreach($comparisonPeriods as $index => $period)
                            <th wire:key="header-period-{{ $index }}-{{ $period['year'] }}" class="px-6 py-8 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right border-l border-slate-100/50">
                                <div class="flex flex-col">
                                    <span class="opacity-60">{{ $period['year'] }}</span>
                                    <span class="text-indigo-500">
                                        {{ $period['quarter'] === 'all' ? 'FULL YEAR' : 'Q'.$period['quarter'].' RELEASES' }}
                                    </span>
                                </div>
                            </th>
                        @endforeach

                        <th class="px-8 py-8 text-[10px] font-black text-indigo-600 uppercase tracking-widest text-right bg-indigo-50/50">
                            <div class="flex flex-col">
                                <span>Variance</span>
                                <span class="text-[9px] text-indigo-400 opacity-80">Trajectory</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-slate-50">
                    @forelse($this->results as $index => $mda)
                        {{-- Unique row key combining index, category and filter to force re-render --}}
                        <tr 
                            wire:key="mda-row-{{ $index }}-{{ $mda->category ?? 'cat' }}-{{ $filter }}" 
                            class="hover:bg-indigo-50/20 transition-all group"
                        >
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-800">
                                        {{ $mda->category ?? 'Unknown' }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 tracking-widest uppercase">
                                        {{ $filter }} ANALYSIS
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-6 text-right border-l border-slate-50/50 bg-slate-50/30">
                                <span class="text-sm font-bold text-slate-600">
                                    @if(isset($mda->total_provision))
                                        ₦{{ number_format($mda->total_provision, 0) }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </span>
                            </td>
                            
                            @foreach($mda->values as $vIndex => $val)
                                <td wire:key="val-{{ $index }}-{{ $vIndex }}" class="px-6 py-6 text-right border-l border-slate-50/50">
                                    <span class="text-sm font-bold text-slate-700">₦{{ number_format($val, 0) }}</span>
                                </td>
                            @endforeach

                            <td class="px-8 py-6 text-right bg-indigo-50/10">
                                <div class="inline-flex flex-col items-end">
                                    @php
                                        $variance = $mda->total_variance ?? 0;
                                        $percent = $mda->variance_percentage ?? 0;
                                        $isPositive = $variance >= 0;
                                    @endphp
                                    
                                    <span class="text-sm font-black {{ $isPositive ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $isPositive ? '↑' : '↓' }} ₦{{ number_format(abs($variance), 0) }}
                                    </span>
                                    
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-md {{ $isPositive ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ number_format($percent, 1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="px-8 py-12 text-center text-slate-400 italic">
                                No comparison data found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>