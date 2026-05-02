{{-- resources/views/livewire/admin/reports/ranking_partial.blade.php --}}
<div class="bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-900 text-white">
                    <th class="px-4 py-5 text-left text-xs font-black uppercase border-r border-gray-700 w-64">
                        MDA Name & Code
                    </th>
                    <th class="px-3 py-5 text-right text-[12px] uppercase bg-blue-900/50 border-r border-gray-700">
                        Revenue
                    </th>
                    <th class="px-3 py-5 text-right text-[12px] uppercase bg-green-900/50 border-r border-gray-700">
                        Personnel
                    </th>
                    <th class="px-3 py-5 text-right text-[12px] uppercase bg-emerald-900/50 border-r border-gray-700">
                        Overhead
                    </th>
                    <th class="px-3 py-5 text-right text-[12px] uppercase bg-purple-900/50 border-r border-gray-700">
                        Capital
                    </th>
                    <th class="px-4 py-5 text-right text-xs font-black uppercase bg-yellow-600 text-gray-900">
                        Total Spending
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($dataset ?? [] as $mda)
                <tr class="hover:bg-gray-50 transition-colors">
                    {{-- MDA Identification --}}
                    <td class="px-4 py-4 text-xs font-bold text-gray-800 border-r border-gray-100 uppercase leading-tight">
                        <div class="flex flex-col">
                            <div class="flex items-center mb-1">
                                <span class="text-gray-400 mr-2 font-normal text-[10px]">#{{ $loop->iteration }}</span>
                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[9px] font-mono font-bold tracking-tighter border border-blue-100">
                                    {{ $mda['mda_code'] ?? 'N/A' }}
                                </span>
                            </div>
                            <span class="block text-[11px]">{{ $mda['mda_name'] }}</span>
                        </div>
                    </td>
                    
                    {{-- Category Totals (Actual Spend) --}}
                    <td class="px-3 py-4 text-right font-mono text-[12px] border-r border-gray-50 text-gray-700">
                        ₦{{ number_format($mda['revenue'] ?? 0, 2) }}
                    </td>
                    <td class="px-3 py-4 text-right font-mono text-[12px] border-r border-gray-50 text-gray-700">
                        ₦{{ number_format($mda['personnel'] ?? 0, 2) }}
                    </td>
                    <td class="px-3 py-4 text-right font-mono text-[12px] border-r border-gray-50 text-gray-700">
                        ₦{{ number_format($mda['overhead'] ?? 0, 2) }}
                    </td>
                    <td class="px-3 py-4 text-right font-mono text-[12px] border-r border-gray-50 text-gray-700">
                        ₦{{ number_format($mda['capital'] ?? 0, 2) }}
                    </td>

                    {{-- Sorting Column: Aggregate Total --}}
                    <td class="px-4 py-4 text-right bg-yellow-50/50">
                        <div class="flex flex-col items-end">
                            <span class="text-sm font-black text-gray-900">
                                ₦{{ number_format($mda['total_spend'] ?? 0, 2) }}
                            </span>
                            {{-- Visual weight indicator (optional) --}}
                            @if(($dataset[0]['total_spend'] ?? 0) > 0)
                                <div class="w-20 bg-gray-200 h-1 rounded-full mt-1 overflow-hidden">
                                    <div class="h-1 bg-yellow-500 rounded-full" 
                                         style="width: {{ ($mda['total_spend'] / $dataset[0]['total_spend']) * 100 }}%">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-20 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-400 italic uppercase text-xs font-bold tracking-widest">
                                No financial records available for this period
                            </span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>