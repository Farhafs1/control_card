<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 uppercase tracking-wider">
                    {{ $filters['quarter'] === 'all' ? 'Full Fiscal Year' : 'Quarter ' . $filters['quarter'] }}
                </span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Comparative Analysis</h1>
            <p class="text-sm text-slate-500 font-medium">Rankings by Fiscal Impact vs. Execution Efficiency</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Quarterly Filter -->
            <select 
                wire:model.live="filters.quarter" 
                class="bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
                <option value="all">All Quarters</option>
                <option value="1">1st Quarter</option>
                <option value="2">2nd Quarter</option>
                <option value="3">3rd Quarter</option>
                <option value="4">4th Quarter</option>
            </select>

            <!-- Toggle Switch -->
            <div class="inline-flex p-1 bg-slate-200/50 rounded-xl border border-slate-200">
                <button 
                    wire:click="setViewMode('spending')"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $viewMode === 'spending' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                    SPENDING
                </button>
                <button 
                    wire:click="setViewMode('efficiency')"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $viewMode === 'efficiency' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                    EFFICIENCY
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Rank</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">MDA Entity</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Actual Amount</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Efficiency Score</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Utilization</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($mdaData as $index => $mda)
                    <tr class="hover:bg-slate-50/80 transition-all group">
                        <!-- Rank Column -->
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-black {{ $index < 3 ? 'text-indigo-600' : 'text-slate-300' }}">
                                    {{ sprintf('%02d', $index + 1) }}
                                </span>
                                @if($index === 0)
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                @endif
                            </div>
                        </td>

                        <!-- MDA Name Column -->
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-1">
                                    {{ $mda->name }}
                                </span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-medium text-slate-400 tracking-tight">{{ $mda->mda_code }}</span>
                                    @if($mda->is_significant)
                                        <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black bg-amber-50 text-amber-600 border border-amber-100 uppercase">
                                            High Impact
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Spending Column -->
                        <td class="px-6 py-5 text-right">
                            <span class="text-sm font-bold text-slate-700 tabular-nums">
                                <span class="text-slate-400 font-medium mr-0.5">₦</span>{{ number_format($mda->actual, 0) }}
                            </span>
                        </td>

                        <!-- Modern Score Column -->
                        <td class="px-6 py-5">
                            <div class="flex flex-col items-center gap-1.5">
                                <span class="text-xs font-black text-slate-800">{{ number_format($mda->weighted_score, 1) }}</span>
                                <div class="relative w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <!-- Dynamic bar width capped at 100% -->
                                    <div 
                                        class="absolute inset-y-0 left-0 bg-indigo-500 rounded-full transition-all duration-700" 
                                        style="width: {{ min($mda->weighted_score, 100) }}%">
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Utilization Column with Modern Color Coding -->
                        <td class="px-8 py-5 text-right">
                            @php
                                $perf = $mda->performance;
                                $colorClass = $perf >= 80 ? 'emerald' : ($perf >= 50 ? 'amber' : 'rose');
                            @endphp
                            <div class="inline-flex items-center gap-2">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-black text-{{ $colorClass }}-600 tracking-tight">
                                        {{ number_format($perf, 1) }}%
                                    </span>
                                    <span class="text-[9px] font-bold text-{{ $colorClass }}-400 uppercase tracking-widest">
                                        {{ $perf >= 80 ? 'Optimal' : ($perf >= 50 ? 'Fair' : 'Under') }}
                                    </span>
                                </div>
                                <div class="w-2 h-8 rounded-full bg-{{ $colorClass }}-100 flex items-end overflow-hidden">
                                    <div class="w-full bg-{{ $colorClass }}-500 transition-all duration-1000" style="height: {{ min($perf, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Empty State -->
        @if(count($mdaData) === 0)
            <div class="p-20 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-slate-900 font-bold">No Data Available</h3>
                <p class="text-slate-500 text-sm">No releases found for the selected quarter.</p>
            </div>
        @endif
    </div>
</div>