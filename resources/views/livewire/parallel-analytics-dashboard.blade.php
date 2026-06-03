<div class="space-y-6 p-6">
    {{-- Header Filtering Panel Control Matrix --}}
    <div class="relative flex flex-wrap items-center justify-between gap-6 p-6 bg-white shadow-md rounded-2xl border border-slate-200/80 overflow-hidden">
        
        {{-- High-Tech Minimalist Accent Bar --}}
        <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-cyan-500 via-indigo-500 to-slate-200"></div>
        
        {{-- Left System Title Section --}}
        <div class="space-y-1 relative z-10">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
                </span>
                <h1 class="text-m font-black text-slate-900 tracking-wider uppercase font-mono">
                    <span class="text-indigo-600 font-sans font-bold text-m px-2 py-0.5 bg-indigo-50 rounded-md border border-indigo-100">Budget Analytics</span>
                </h1>
            </div>
            <p class="text-xs text-slate-500 font-medium">
                Processing complex economic classification structures and transactional updates <span class="text-indigo-600 font-bold font-mono bg-slate-50 px-1 py-0.5 rounded">in-memory</span>.
            </p>
        </div>
        
        {{-- Right Filter Controls Pod Matrix --}}
        <div class="flex flex-wrap lg:flex-nowrap items-center gap-3 w-full lg:w-auto relative z-10">
            
            <div class="grid grid-cols-2 sm:flex items-center gap-3 w-full sm:w-auto">
                <!-- Pod 1: Fiscal Period -->
                <div class="flex flex-col group p-2 bg-slate-50/60 rounded-xl border border-slate-200 transition-all duration-200 focus-within:bg-white focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 min-w-[130px]">
                    <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1 group-focus-within:text-indigo-600 transition-colors">
                        • Fiscal Period
                    </label>
                    <select wire:model.live="filters.quarter" class="w-full border-0 bg-transparent p-1 py-0 text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer appearance-none">
                        <option value="all">Full Fiscal Year</option>
                        <option value="1">Quarter 1</option>
                        <option value="2">Quarter 2</option>
                        <option value="3">Quarter 3</option>
                        <option value="4">Quarter 4</option>
                    </select>
                </div>

                <!-- Pod 2: Ledger Type -->
                <div class="flex flex-col group p-2 bg-slate-50/60 rounded-xl border border-slate-200 transition-all duration-200 focus-within:bg-white focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 min-w-[130px]">
                    <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1 group-focus-within:text-indigo-600 transition-colors">
                        • Ledger Type
                    </label>
                    <select wire:model.live="filters.type" class="w-full border-0 bg-transparent p-1 py-0 text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer appearance-none">
                        <option value="">All Transactions</option>
                        <option value="Revenue">Revenue (Inflows)</option>
                        <option value="Expenditure">Expenditure (Outflows)</option>
                    </select>
                </div>

                <!-- Pod 3: GFSM Classification Group -->
                <div class="flex flex-col group p-2 bg-slate-50/60 rounded-xl border border-slate-200 transition-all duration-200 focus-within:bg-white focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 min-w-[160px]">
                    <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1 group-focus-within:text-indigo-600 transition-colors">
                        • GFSM Group
                    </label>
                    <select wire:model.live="filters.category" class="w-full border-0 bg-transparent p-1 py-0 text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer appearance-none">
                        <option value="">All Sub-Categories</option>
                        <optgroup label="Revenue Frameworks" class="bg-white text-slate-400 font-bold text-[10px]">
                            <option value="Revenue_FAAC">FAAC Allocations</option>
                            <option value="Revenue_IGR">Internal Generated Revenue (IGR)</option>
                            <option value="Revenue_Aid_Grant">Aids & Grants Frameworks</option>
                            <option value="Revenue_Capital_Receipt">Capital Receipts</option>
                        </optgroup>
                        <optgroup label="Expenditure Frameworks" class="bg-white text-slate-400 font-bold text-[10px]">
                            <option value="Expenditure_Personnel">Personnel Costs Allocation</option>
                            <option value="Expenditure_Overhead">Overhead Operational Costs</option>
                            <option value="Expenditure_Capital">Capital Project Development</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Pod 4: Group Table Metrics By -->
                <div class="flex flex-col group p-2 bg-slate-50/60 rounded-xl border border-slate-200 transition-all duration-200 focus-within:bg-white focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 min-w-[160px]">
                    <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1 group-focus-within:text-indigo-600 transition-colors">
                        • Dimension Axis
                    </label>
                    <select wire:model.live="filters.groupBy" class="w-full border-0 bg-transparent p-1 py-0 text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer appearance-none">
                        <option value="category">Economic Classification (GFSM)</option>
                        <option value="mda">Administrative Framework (MDA Level)</option>
                        <option value="sector">Functional Categories (Sectors)</option>
                    </select>
                </div>
            </div>

            <!-- NEW Pod 5: Integrated Export Terminal Actions Actions -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-100/90 rounded-xl border border-slate-200/50 w-full lg:w-auto justify-center sm:justify-start">
                <div class="hidden xl:flex flex-col pr-2.5 pl-1.5 border-r border-slate-200 text-left">
                    <span class="text-[8px] font-black uppercase text-slate-400 tracking-widest leading-tight">• Output</span>
                    <span class="text-[10px] font-black text-slate-600 tracking-tight font-mono">Terminal</span>
                </div>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button wire:click="exportToExcel" 
                            wire:loading.attr="disabled"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-emerald-200 text-xs font-bold text-emerald-700 shadow-sm hover:bg-emerald-600 hover:text-white hover:border-emerald-600 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:pointer-events-none group"
                            title="Compile filtered state into native modern Excel Spreadsheet document">
                        <svg class="w-3.5 h-3.5 text-emerald-600 group-hover:text-white transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="font-mono tracking-wide">XLSX</span>
                    </button>

                    <button wire:click="exportToPdf" 
                            wire:loading.attr="disabled"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-rose-200 text-xs font-bold text-rose-700 shadow-sm hover:bg-rose-600 hover:text-white hover:border-rose-600 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:pointer-events-none group"
                            title="Compile filtered state into Ledger Document PDF format">
                        <svg class="w-3.5 h-3.5 text-rose-600 group-hover:text-white transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-mono tracking-wide">PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Level 2 Summary Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        
        <!-- Card 1: Opening Balance -->
        <div class="relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between overflow-hidden">
            <div class="absolute top-0 left-0 bottom-0 w-[4px] bg-slate-400"></div>
            <div class="space-y-1 pl-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">• Opening Balance</p>
                <h3 class="text-xl xl:text-2xl font-black text-slate-800 font-mono tracking-tight truncate select-all" title="₦{{ number_format($stats['opening_balance'], 2) }}">
                    ₦{{ number_format($stats['opening_balance'], 2) }}
                </h3>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 pl-1">
                <span class="text-[10px] text-slate-400 font-medium font-mono">Brought Forward Base</span>
            </div>
        </div>

        <!-- Card 2: Total Revenue -->
        <div class="relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between overflow-hidden">
            <div class="absolute top-0 left-0 bottom-0 w-[4px] bg-emerald-500"></div>
            <div class="space-y-1 pl-1">
                <p class="text-[10px] font-black text-emerald-600/90 uppercase tracking-widest">• Total Revenue (Inflows)</p>
                <h3 class="text-xl xl:text-2xl font-black text-emerald-600 font-mono tracking-tight truncate select-all" title="₦{{ number_format($stats['revenue']['actual'], 2) }}">
                    ₦{{ number_format($stats['revenue']['actual'], 2) }}
                </h3>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 pl-1 flex items-center justify-between">
                <span class="text-[10px] text-slate-400 font-medium truncate mr-1" title="Target: ₦{{ number_format($stats['revenue']['budget'], 2) }}">
                    Target: <span class="font-mono">₦{{ number_format($stats['revenue']['budget'], 0) }}</span>
                </span>
                <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-mono">
                    {{ $stats['revenue']['percentage'] }}%
                </span>
            </div>
        </div>

        <!-- Card 3: Total Expenditure -->
        <div class="relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between overflow-hidden">
            <div class="absolute top-0 left-0 bottom-0 w-[4px] bg-rose-500"></div>
            <div class="space-y-1 pl-1">
                <p class="text-[10px] font-black text-rose-600/90 uppercase tracking-widest">• Total Expenditure (Outflows)</p>
                <h3 class="text-xl xl:text-2xl font-black text-rose-600 font-mono tracking-tight truncate select-all" title="₦{{ number_format($stats['expenditure']['actual'], 2) }}">
                    ₦{{ number_format($stats['expenditure']['actual'], 2) }}
                </h3>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 pl-1 flex items-center justify-between">
                <span class="text-[10px] text-slate-400 font-medium truncate mr-1" title="Budget: ₦{{ number_format($stats['expenditure']['budget'], 2) }}">
                    Budget: <span class="font-mono">₦{{ number_format($stats['expenditure']['budget'], 0) }}</span>
                </span>
                <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 font-mono">
                    {{ $stats['expenditure']['percentage'] }}%
                </span>
            </div>
        </div>

        <!-- Card 4: Net Treasury Position -->
        <div class="relative p-5 rounded-2xl border flex flex-col justify-between overflow-hidden transition-all duration-200 
            {{ $stats['net_cash_position'] >= 0 ? 'bg-emerald-50/40 border-emerald-200 shadow-sm' : 'bg-rose-50/40 border-rose-200 shadow-sm' }}">
            <div class="absolute top-0 left-0 bottom-0 w-[4px] {{ $stats['net_cash_position'] >= 0 ? 'bg-emerald-600' : 'bg-rose-600' }}"></div>
            
            <div class="space-y-1 pl-1">
                <p class="text-[10px] font-black uppercase tracking-widest {{ $stats['net_cash_position'] >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                    • Net Treasury Position
                </p>
                <h3 class="text-xl xl:text-2xl font-black font-mono tracking-tight truncate select-all {{ $stats['net_cash_position'] >= 0 ? 'text-emerald-900' : 'text-rose-900' }}" title="₦{{ number_format($stats['net_cash_position'], 2) }}">
                    ₦{{ number_format($stats['net_cash_position'], 2) }}
                </h3>
            </div>
            
            <div class="mt-3 pt-2 border-t pl-1 {{ $stats['net_cash_position'] >= 0 ? 'border-emerald-200/60' : 'border-rose-200/60' }}">
                <p class="text-[9px] font-semibold tracking-wide {{ $stats['net_cash_position'] >= 0 ? 'text-emerald-700/80' : 'text-rose-700/80' }}">
                    Formula: (Opening + Revenue) - Expenditure
                </p>
            </div>
        </div>

    </div>

    {{-- STACKED LAYOUT LAYER --}}
    <div class="space-y-6">
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden w-full">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Performance Summary Tracking Matrix</h2>
                <span class="text-xs text-slate-500 font-semibold bg-white px-3 py-1 rounded-full border">
                    Evaluating {{ count($performance) }} Group Vectors
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[10px] uppercase font-black text-slate-500 tracking-wider border-b">
                            <th class="px-6 py-3.5">Budget Component Group Label</th>
                            <th class="px-6 py-3.5 text-right">Adjusted Provision (₦)</th>
                            <th class="px-6 py-3.5 text-right">Actual Performance (₦)</th>
                            <th class="px-6 py-3.5 text-center">Absorption Rate</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
                        @forelse($performance as $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 text-slate-900 font-bold max-w-md truncate">{{ $row['name'] }}</td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">{{ number_format($row['total_budget'], 2) }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold {{ $row['type'] === 'Revenue' ? 'text-emerald-600' : 'text-slate-900' }}">{{ number_format($row['total_actual'], 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-slate-700">{{ $row['percentage'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
                                        @if($row['status'] === 'success') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($row['status'] === 'warning') bg-amber-50 text-amber-700 border border-amber-200
                                        @else bg-rose-50 text-rose-700 border border-rose-200 @endif">
                                        {{ $row['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-bold">No data match selections.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6 transition-all duration-300 hover:shadow-md">
    
                {{-- Header with Sector Vector Identity Node --}}
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider font-sans">
                            Functional Sector Matrix
                        </h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 pl-7">
                        Cross-sectional budget execution aggregated by administrative functional domains.
                    </p>
                </div>
                
                {{-- High-Visibility Linear Execution Vectors --}}
                <div class="space-y-5">
                    @foreach($sectors as $sec)
                        <div class="group space-y-2 p-2 -mx-2 rounded-xl transition-all duration-200 hover:bg-slate-50/60">
                            
                            {{-- Label & Meta Percentage Tracking Core --}}
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-700 group-hover:text-slate-900 transition-colors truncate max-w-[75%]" title="{{ $sec['name'] }}">
                                    • {{ $sec['name'] }}
                                </span>
                                <span class="font-mono transition-colors text-[11px] px-2 py-0.5 rounded font-black
                                    {{ $sec['percentage'] > 100 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-800 group-hover:bg-indigo-50 group-hover:text-indigo-700' }}">
                                    {{ $sec['percentage'] }}%
                                </span>
                            </div>
                            
                            {{-- Sleekened Minimalist Progress Rail --}}
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden border border-slate-200/40">
                                <div class="h-full rounded-full bg-indigo-600 transition-all duration-500 ease-out group-hover:bg-indigo-500 shadow-[inset_0_1px_0_rgba(255,255,255,0.2)]" 
                                    style="width: {{ min($sec['percentage'], 100) }}%"></div>
                            </div>
                            
                            {{-- Financial Precision Matrix Metrics --}}
                            <div class="flex justify-between text-[10px] text-slate-400 font-semibold font-mono pl-1">
                                <span class="truncate mr-2" title="Actual: ₦{{ number_format($sec['actual'], 2) }}">
                                    Actual: <span class="text-slate-600 group-hover:text-slate-800">₦{{ number_format($sec['actual'], 0) }}</span>
                                </span>
                                <span class="truncate" title="Budget: ₦{{ number_format($sec['budget'], 2) }}">
                                    Budget: <span class="text-slate-500">₦{{ number_format($sec['budget'], 0) }}</span>
                                </span>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5 transition-all duration-300 hover:shadow-md">
    
                {{-- Header with Mini Radar/Identity Node --}}
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider font-sans">
                            Quarterly Performance Trend
                        </h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 pl-7">
                        Continuous sequence growth vectors across the current execution cycle.
                    </p>
                </div>

                {{-- Interactive Sequential Pod Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($trends as $qLabel => $qValue)
                        <div class="group relative p-4 bg-slate-50/70 border border-slate-200 rounded-xl flex flex-col justify-between overflow-hidden transition-all duration-200 hover:bg-white hover:border-indigo-500/50 hover:shadow-[0_4px_12px_rgba(99,102,241,0.06)]">
                            
                            {{-- Sleek subtle hover element indicator bar --}}
                            <div class="absolute top-0 bottom-0 left-0 w-[3px] bg-transparent transition-colors duration-200 group-hover:bg-indigo-500"></div>
                            
                            <div class="space-y-1.5 pl-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest group-hover:text-indigo-600 transition-colors">
                                        • {{ $qLabel }} Outflows
                                    </span>
                                    
                                    {{-- Minimalist analytical sequence metric indicator badge --}}
                                    <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-200/60 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-700 transition-colors uppercase font-bold">
                                        SEQ-{{ $loop->iteration }}
                                    </span>
                                </div>
                                
                                <h4 class="text-base font-black text-slate-800 font-mono tracking-tight select-all truncate" title="₦{{ number_format($qValue, 2) }}">
                                    ₦{{ number_format($qValue, 2) }}
                                </h4>
                            </div>

                            {{-- Interactive contextual mini bar gauge to hint layout depth --}}
                            <div class="mt-3 pt-2 border-t border-slate-100/80 pl-1 flex items-center justify-between text-[10px] text-slate-400 font-semibold font-mono">
                                <span>Vect. Active</span>
                                <span class="inline-block w-1.5 h-1.5 rounded-full {{ $qValue > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' }}"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>