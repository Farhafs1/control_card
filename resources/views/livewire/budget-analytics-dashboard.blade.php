<div class="space-y-6">
    {{-- Header & Filter Bar --}}
        <div class="no-print flex flex-wrap items-center gap-6 p-5 bg-white shadow-sm rounded-2xl border border-slate-200">
        
        {{-- Fiscal Year: Static Display --}}
        <div class="flex flex-col">
            <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1">Fiscal Year</label>
            <div class="px-4 py-2 bg-slate-100 rounded-xl text-sm font-black text-slate-700 border border-transparent">
                2026
            </div>
        </div>

        {{-- Period Selector --}}
        <div class="flex flex-col min-w-[180px]">
            <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1 flex justify-between">
                Period
                <span wire:loading wire:target="filters.quarter" class="animate-spin h-3 w-3 border-2 border-indigo-500 border-t-transparent rounded-full"></span>
            </label>
            <select wire:model.live="filters.quarter" class="rounded-xl border-slate-200 bg-slate-50 text-sm font-medium focus:ring-slate-900 focus:border-slate-900 transition-all">
                <option value="all">Full Fiscal Year</option>
                <option value="1">1st Quarter (Jan-Mar)</option>
                <option value="2">2nd Quarter (Apr-Jun)</option>
                <option value="3">3rd Quarter (Jul-Sep)</option>
                <option value="4">4th Quarter (Oct-Dec)</option>
            </select>
        </div>

        {{-- Type Selector --}}
        <div class="flex flex-col min-w-[140px]">
            <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1">Account Type</label>
            <select wire:model.live="filters.type" class="rounded-xl border-slate-200 bg-white text-sm font-medium focus:ring-slate-900 focus:border-slate-900 transition-all">
                <option value="">All Transactions</option>
                <option value="Revenue">Revenue</option>
                <option value="Expenditure">Expenditure</option>
            </select>
        </div>

        {{-- Category Selector --}}
        <div class="flex flex-col min-w-[160px]">
            <label class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1">Category</label>
            <select wire:model.live="filters.category" class="rounded-xl border-slate-200 bg-white text-sm font-medium focus:ring-slate-900 focus:border-slate-900 transition-all">
                <option value="">All Categories</option>
                @if(($filters['type'] ?? '') === 'Revenue')
                    <optgroup label="Revenue Sources">
                        <option value="Revenue_FAAC">FAAC</option>
                        <option value="Revenue_IGR">IGR</option>
                        <option value="Revenue_Aid_Grant">Aids & Grants</option>
                    </optgroup>
                @elseif(($filters['type'] ?? '') === 'Expenditure')
                    <optgroup label="Expenditure Heads">
                        <option value="Expenditure_Personnel">Personnel</option>
                        <option value="Expenditure_Overhead">Overhead</option>
                        <option value="Expenditure_Capital">Capital</option>
                    </optgroup>
                @endif
            </select>
        </div>

        {{-- Actions Section --}}
        <div class="flex items-center gap-4 ml-auto self-end mb-1">
            <button wire:click="resetFilters" class="text-xs font-bold text-slate-400 hover:text-rose-500 uppercase tracking-tighter transition-colors">
                Clear
            </button>
            
            <div class="h-8 w-[1px] bg-slate-100 mx-2"></div>

            {{-- Direct Print Button --}}
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Report
            </button>

            <button wire:click="downloadPdf" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Executive PDF
            </button>
        </div>
    </div>

        {{-- Top Summary Stats --}}
    <div wire:loading.class="opacity-50" class="grid grid-cols-1 md:grid-cols-7 gap-6">
        
        {{-- 1. Total Revenue (Span 2) --}}
        <div class="md:col-span-2 bg-white p-7 rounded-2xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Total Revenue</h3>
            <div class="flex flex-col mt-2">
                <span class="text-3xl font-black tracking-tight text-slate-900 leading-none">
                    ₦{{ number_format($stats['revenue']['actual'] ?? 0) }}
                </span>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($stats['revenue']['percentage'] ?? 0) >= 75 ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                        {{ $stats['revenue']['percentage'] ?? 0 }}%
                    </span>
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-tighter">collected</span>
                </div>
            </div>
        </div>

        {{-- 2. Total Expenditure (Span 2) --}}
        <div class="md:col-span-2 bg-white p-7 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Expenditure</h3>
            <div class="flex flex-col mt-2">
                <span class="text-3xl font-black tracking-tight text-slate-900 leading-none">
                    ₦{{ number_format($stats['expenditure']['actual'] ?? 0) }}
                </span>
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($stats['expenditure']['status'] ?? '') === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }}">
                        {{ $stats['expenditure']['percentage'] ?? 0 }}%
                    </span>
                    <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">of budget spent</span>
                </div>
            </div>
        </div>

        {{-- 3. Net Position (Span 2) --}}
        <div class="md:col-span-2 bg-slate-900 p-7 rounded-2xl shadow-xl">
            <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Net Cash Position</h3>
            <div class="flex flex-col mt-2">
                <span class="text-3xl font-black text-white leading-none">
                    ₦{{ number_format($stats['net_cash_position'] ?? 0) }}
                </span>
                <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-800">
                    <div class="flex flex-col">
                        <span class="text-[9px] text-slate-500 uppercase font-bold">Opening Balance</span>
                        <span class="text-xs text-slate-300 font-mono">₦{{ number_format($stats['opening_balance'] ?? 0) }}</span>
                    </div>
                    <div class="ml-auto text-[10px] text-slate-500 italic uppercase text-right">
                        {{ $filters['quarter'] == 'all' ? 'FY 2026' : 'Q'.$filters['quarter'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Fiscal Health (Span 1) --}}
        <div class="md:col-span-1 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Health</h3>
            <div class="mt-2">
                @php 
                    $colorMap = ['success'=>'emerald','warning'=>'amber','danger'=>'rose','info'=>'blue','neutral'=>'slate'];
                    $color = $colorMap[$stats['expenditure']['status'] ?? 'slate'] ?? 'slate';
                @endphp
                <div class="flex items-center gap-2">
                    <div class="relative flex h-2.5 w-2.5 shrink-0">
                        <span class="animate-ping absolute h-full w-full rounded-full bg-{{ $color }}-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-{{ $color }}-500"></span>
                    </div>
                    <span class="text-sm font-black text-slate-900 truncate">
                        {{ $stats['expenditure']['label'] ?? 'Standby' }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- Main Analysis Content - Changed lg:grid-cols-3 gap-8 to full width --}}
    <div class="w-full bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden h-fit">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest">Budget Summary Analysis</h3>
            <span class="text-[10px] font-bold text-slate-400 italic">Aggregated Totals</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100/80 border-b border-slate-200">
                    <tr>
                        {{-- Component Column --}}
                        <th class="px-6 py-4 text-left">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.15em]">Budget Component</span>
                        </th>

                        {{-- Financial Columns --}}
                        <th class="px-4 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Approved</span>
                                <span class="text-[9px] font-medium text-slate-400 uppercase leading-none mt-0.5">FY 2026 Provision</span>
                            </div>
                        </th>

                        <th class="px-4 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Revised</span>
                                <span class="text-[9px] font-medium text-slate-400 uppercase leading-none mt-0.5">Final Allocation</span>
                            </div>
                        </th>

                        <th class="px-4 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-900 uppercase tracking-wider">Actuals</span>
                                <span class="text-[9px] font-bold text-emerald-600 uppercase leading-none mt-0.5">Performance</span>
                            </div>
                        </th>

                        <th class="px-4 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Variance</span>
                                <span class="text-[9px] font-medium text-slate-400 uppercase leading-none mt-0.5">Over/Under</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-slate-100">
                    @forelse($performance as $row)
                        @php 
                            $rowStatus = $row->status ?? 'slate'; 
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            {{-- 1. Component --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $row->display_label ?? 'Unknown' }}</div>
                                <div class="text-[10px] text-slate-400 tracking-tight">{{ $row->item_count ?? 0 }} Lines Aggregated</div>
                            </td>

                            {{-- 2. Approved Provision --}}
                            <td class="px-4 py-4 text-right text-sm text-slate-600 font-medium">
                                ₦{{ number_format($row->budget ?? 0) }}
                            </td>

                            {{-- 3. Revised Provision --}}
                            <td class="px-4 py-4 text-right text-sm text-slate-600 font-medium">
                                ₦{{ number_format($row->revised_provision ?? 0) }}
                            </td>

                            {{-- 4. Actual Performance --}}
                            <td class="px-4 py-4 text-right text-sm font-black text-slate-900">
                                ₦{{ number_format($row->actual ?? 0) }}
                                <div class="text-[10px] text-{{ $rowStatus }}-600">{{ round($row->percentage ?? 0) }}% Utilization</div>
                            </td>

                            {{-- 5. Variance --}}
                            <td class="px-4 py-4 text-right">
                                <div class="text-sm font-bold {{ ($row->variance ?? 0) < 0 ? 'text-red-600' : 'text-slate-700' }}">
                                    ₦{{ number_format($row->variance ?? 0) }}
                                </div>

                                {{-- Fixed Line Below --}}
                                @php $status = $this->getPerformanceStatus($row->percentage ?? 0); @endphp
                                
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $status['color'] }}-100 text-{{ $status['color'] }}-700 border border-{{ $status['color'] }}-200">
                                    <span class="h-1 w-1 rounded-full bg-{{ $status['color'] }}-500"></span>
                                    {{ $status['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty State Logic --}}
                    @endforelse
                </tbody>

                {{-- New: Sleek Total Row Footer --}}
                @if($performance->count() > 0)
                    <tfoot class="bg-slate-900 border-t-4 border-emerald-500">
                        <tr class="text-white">
                            {{-- Label with icon --}}
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <span class="p-1.5 bg-slate-800 rounded-lg">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </span>
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Grand Total</span>
                                </div>
                            </td>

                            {{-- Budget Figures --}}
                            <td class="px-4 py-5 text-right font-mono text-sm font-bold border-r border-slate-800">
                                ₦{{ number_format($performance->sum('budget')) }}
                            </td>
                            <td class="px-4 py-5 text-right font-mono text-sm font-bold border-r border-slate-800">
                                ₦{{ number_format($performance->sum('revised_provision')) }}
                            </td>
                            <td class="px-4 py-5 text-right font-mono text-sm font-bold border-r border-slate-800 text-emerald-400">
                                ₦{{ number_format($performance->sum('actual')) }}
                            </td>

                            {{-- Variance with Dynamic Color --}}
                            @php $totalVariance = $performance->sum('variance'); @endphp
                            <td class="px-4 py-5 text-right font-mono text-sm font-bold {{ $totalVariance < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                <div class="flex flex-col items-end">
                                    <span>₦{{ number_format($totalVariance) }}</span>
                                    <span class="text-[9px] opacity-50 uppercase tracking-tighter leading-none mt-1">Net Variance</span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
            {{-- Table Footnote: Net Cash Position Formula --}}
            <div class="mt-4 px-6 py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <div class="flex items-start gap-3">
                    <span class="text-indigo-600 mt-0.5">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <h4 class="text-[10px] font-black uppercase text-slate-500 tracking-wider mb-1">Financial Note</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            * <span class="font-bold text-slate-900">Net Cash Position</span> is calculated as: 
                            <span class="inline-block px-2 py-0.5 bg-white border border-slate-200 rounded font-mono text-indigo-700 ml-1">
                                (Opening Balance + Total Revenue) - Total Expenditure
                            </span>
                        </p>
                        <p class="mt-1 text-[10px] text-slate-400 italic">
                            This represents the actual liquid surplus available for the remaining fiscal period.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Full Page Loading Overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-[9999] items-center justify-center bg-white/60 backdrop-blur-sm">
        <div class="flex flex-col items-center">
            {{-- Modern Spinner --}}
            <div class="relative h-16 w-16">
                <div class="absolute inset-0 rounded-full border-4 border-slate-200"></div>
                <div class="absolute inset-0 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin"></div>
            </div>
            
            {{-- Loading Text --}}
            <span class="mt-4 text-sm font-black text-slate-700 uppercase tracking-widest animate-pulse">
                Loading Analytics...
            </span>
        </div>
    </div>



    {{-- Add this style block at the bottom of your Blade file --}}
    <style>
        @media print {
            /* 1. Reset Page Margins */
            @page {
                size: A4;
                margin: 10mm;
            }

            /* 2. Hide everything except the data */
            .no-print, button, select, nav, aside, .filter-bar { 
                display: none !important; 
            }

            /* 3. Collapse the Grid into a tight row */
            .grid { 
                display: flex !important; 
                flex-direction: row !important;
                gap: 10px !important;
                margin-bottom: 15px !important;
            }

            /* 4. Shrink the Cards */
            .grid > div {
                flex: 1 !important;
                padding: 10px !important;
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                margin: 0 !important;
                height: auto !important;
            }

            /* Adjust card text for paper */
            .text-3xl { font-size: 1.25rem !important; } /* Shrink big numbers */
            .text-lg { font-size: 0.875rem !important; }
            h3 { font-size: 8px !important; }

            /* 5. Tighten the Table */
            table {
                width: 100% !important;
                font-size: 10px !important; /* Smaller text to fit columns */
                border-collapse: collapse !important;
            }

            th, td {
                padding: 4px 8px !important; /* Minimal padding */
                border-bottom: 1px solid #e2e8f0 !important;
            }

            /* Ensure background colors (Net Position card) show up */
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }

            /* Prevent page breaks inside cards or table rows */
            tr, .grid > div {
                page-break-inside: avoid;
            }
        }
    </style>
</div>