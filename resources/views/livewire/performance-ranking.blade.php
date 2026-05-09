<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 uppercase tracking-wider">
                    {{ $filters['quarter'] === 'all' ? 'Full Fiscal Year' : 'Quarter ' . $filters['quarter'] }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider">
                    @if($filters['type'] === 'revenue')
                        Revenue Performance
                    @else
                        {{ str_replace('_', ' ', $filters['category'] === 'all' ? 'Total Expenditure' : $filters['category']) }}
                    @endif
                </span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                {{ $filters['type'] === 'revenue' ? 'Revenue Rankings' : 'Expenditure Rankings' }}
            </h1>
            <p class="text-sm text-slate-500 font-medium">Rankings by Fiscal Impact vs. Execution Efficiency</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- 1. Top Level: Revenue vs Other Expenditure -->
            <div class="relative">
                <select 
                    wire:model.live="filters.type" 
                    class="appearance-none bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-xl pl-4 pr-10 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all outline-none cursor-pointer">
                    <option value="expenditure">Other Expenditures</option>
                    <option value="revenue">Revenue Only</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>

            <!-- 2. Conditional Sub-filter: Expenditure Categories -->
            @if($filters['type'] === 'expenditure')
                <div class="relative">
                    <select 
                        wire:model.live="filters.category" 
                        class="appearance-none bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-xl pl-4 pr-10 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all outline-none cursor-pointer">
                        <option value="all">All Expenditures</option>
                        <option value="PERSONNEL">Personnel Cost</option>
                        <option value="OVERHEAD">Recurrent Overhead</option>
                        <option value="CAPITAL">Capital Expenditure</option>
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
            @endif

            <!-- 3. Quarterly Filter -->
            <div class="relative">
                <select 
                    wire:model.live="filters.quarter" 
                    class="appearance-none bg-white border border-slate-200 text-slate-600 text-xs font-bold rounded-xl pl-4 pr-10 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all outline-none cursor-pointer">
                    <option value="all">Full Year</option>
                    <option value="1">1st Quarter</option>
                    <option value="2">2nd Quarter</option>
                    <option value="3">3rd Quarter</option>
                    <option value="4">4th Quarter</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>

            <!-- 4. View Mode Toggle (Harmonized with $filters array) -->
            <div class="inline-flex p-1 bg-slate-200/50 rounded-xl border border-slate-200">
                <button 
                    type="button"
                    wire:click="$set('filters.viewMode', 'spending')"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $filters['viewMode'] === 'spending' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                    BY {{ $filters['type'] === 'revenue' ? 'COLLECTION' : 'SPENDING' }}
                </button>
                <button 
                    type="button"
                    wire:click="$set('filters.viewMode', 'utilization')"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $filters['viewMode'] === 'utilization' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                    BY UTILIZATION
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <!-- Table content remains largely the same, but check variables -->
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Rank</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">MDA Entity</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">
                        {{ $filters['type'] === 'revenue' ? 'Actual Collection' : 'Actual Amount' }}
                    </th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Score</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Performance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($mdaData as $index => $mda)
                    <tr class="hover:bg-slate-50/80 transition-all group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-black {{ $index < 3 ? 'text-indigo-600' : 'text-slate-300' }}">
                                    {{ sprintf('%02d', $index + 1) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-1">
                                    {{ $mda->name }}
                                </span>
                                <span class="text-[10px] font-medium text-slate-400 tracking-tight">{{ $mda->mda_code }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <span class="text-sm font-bold text-slate-700 tabular-nums">
                                <span class="text-slate-400 font-medium mr-0.5">₦</span>{{ number_format($mda->actual, 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col items-center gap-1.5">
                                <span class="text-xs font-black text-slate-800">{{ number_format($mda->weighted_score, 1) }}</span>
                                <div class="relative w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="absolute inset-y-0 left-0 bg-indigo-500 rounded-full transition-all duration-700" 
                                         style="width: {{ min($mda->weighted_score, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right">
                            @php
                                // Note: Ensure $mda object has 'performance_percentage' property as used in the Class logic
                                $perf = $mda->performance_percentage ?? 0;
                                $color = $perf >= 80 ? 'emerald' : ($perf >= 50 ? 'amber' : 'rose');
                                $label = $perf >= 80 ? 'Optimal' : ($perf >= 50 ? 'Fair' : 'Under');
                            @endphp
                            <div class="inline-flex items-center gap-2">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-black text-{{ $color }}-600 tracking-tight">
                                        {{ number_format($perf, 1) }}%
                                    </span>
                                    <span class="text-[9px] font-bold text-{{ $color }}-400 uppercase tracking-widest">
                                        {{ $label }}
                                    </span>
                                </div>
                                <div class="w-2 h-8 rounded-full bg-{{ $color }}-100 flex items-end overflow-hidden">
                                    <div class="w-full bg-{{ $color }}-500 transition-all duration-1000" style="height: {{ min($perf, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>