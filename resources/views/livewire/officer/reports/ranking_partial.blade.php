{{-- resources/views/livewire/officer/reports/ranking_partial.blade.php --}}

<div class="w-full bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200">
    <div class="overflow-x-auto w-full">
        <table class="w-full border-collapse table-auto">
            <thead>
                <tr class="bg-gray-900 text-white">
                    <th class="px-6 py-6 text-left text-sm font-black uppercase border-r border-gray-700 min-w-[300px]">MDA Name & Code</th>
                    <th class="px-4 py-6 text-right text-sm font-bold uppercase bg-blue-900/50 border-r border-gray-700">Revenue</th>
                    <th class="px-4 py-6 text-right text-sm font-bold uppercase bg-green-900/50 border-r border-gray-700">Personnel</th>
                    <th class="px-4 py-6 text-right text-sm font-bold uppercase bg-emerald-900/50 border-r border-gray-700">Overhead</th>
                    <th class="px-4 py-6 text-right text-sm font-bold uppercase bg-purple-900/50 border-r border-gray-700">Capital</th>
                    <th class="px-6 py-6 text-right text-sm font-black uppercase bg-yellow-600 text-gray-900">Total Spending</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($dataset ?? [] as $mda)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-5 text-sm font-bold text-gray-800 border-r border-gray-100 uppercase leading-normal">
                        <div class="flex flex-col">
                            <div class="flex items-center mb-1">
                                <span class="text-gray-400 mr-2 font-normal text-xs">#{{ $loop->iteration }}</span>
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-mono font-bold border border-blue-100">
                                    {{ $mda['mda_code'] ?? 'N/A' }}
                                </span>
                            </div>
                            <span class="block text-sm tracking-tight">{{ $mda['mda_name'] }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-5 text-right font-mono text-sm border-r border-gray-50 text-gray-700">₦{{ number_format($mda['revenue'] ?? 0, 2) }}</td>
                    <td class="px-4 py-5 text-right font-mono text-sm border-r border-gray-50 text-gray-700">₦{{ number_format($mda['personnel'] ?? 0, 2) }}</td>
                    <td class="px-4 py-5 text-right font-mono text-sm border-r border-gray-50 text-gray-700">₦{{ number_format($mda['overhead'] ?? 0, 2) }}</td>
                    <td class="px-4 py-5 text-right font-mono text-sm border-r border-gray-50 text-gray-700">₦{{ number_format($mda['capital'] ?? 0, 2) }}</td>
                    <td class="px-6 py-5 text-right bg-yellow-50/50">
                        <div class="flex flex-col items-end">
                            <span class="text-base font-black text-gray-900">₦{{ number_format($mda['total_spend'] ?? 0, 2) }}</span>
                            @if(($dataset[0]['total_spend'] ?? 0) > 0)
                                <div class="w-24 bg-gray-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-yellow-500 rounded-full" 
                                         style="width: {{ min(($mda['total_spend'] / $dataset[0]['total_spend']) * 100, 100) }}%">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-32 text-center text-gray-400 italic uppercase text-sm font-bold tracking-widest">
                        No financial records available for this period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>