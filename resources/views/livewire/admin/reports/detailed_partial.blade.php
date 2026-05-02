<div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-green-700 text-white uppercase text-xs font-bold">
                <th class="px-4 py-4 border-b">Code</th>
                <th class="px-4 py-4 border-b w-1/3">Description (Subhead)</th>
                <th class="px-4 py-4 border-b text-right">Approved Provision</th>
                <th class="px-4 py-4 border-b text-right">Actual Qtr (Total)</th>
                <th class="px-4 py-4 border-b text-center">Utilization (%)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($results as $mda)
                @php
                    // 1. Calculate MDA-level totals for the header row
                    $mdaTotalProv = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
                    $mdaTotalActual = $mda->subheads->sum('releases_sum_amount');
                    
                    // MDA Performance = (Sum of all releases / Sum of all provisions) * 100
                    $mdaPerf = $mdaTotalProv > 0 ? ($mdaTotalActual / $mdaTotalProv) * 100 : 0;
                @endphp

                {{-- MDA DEMARCATION ROW --}}
                <tr class="bg-gray-800">
                    <td colspan="5" class="px-4 py-3">
                        <div class="flex justify-between items-center">
                            <span class="text-white font-black text-sm tracking-widest uppercase">
                                <span class="ml-2 text-gray-400 font-normal normal-case text-xs"> {{ $mda->mda_code }} -</span>
                                {{ $mda->name }}
                            </span>
                            
                            <span class="text-xs font-bold px-3 py-1 rounded bg-gray-700 {{ $mdaPerf > 25 ? 'text-green-400' : 'text-amber-400' }}">
                                MDA Performance: {{ number_format($mdaPerf, 1) }}%
                            </span>
                        </div>
                    </td>
                </tr>

                @foreach($mda->subheads as $subhead)
                    @php
                        // 2. Individual Subhead Logic
                        $totalProvision = $subhead->approved_provision + $subhead->additional_provision;
                        
                        // Using the aggregated releases sum from the controller
                        $actual = $subhead->releases_sum_amount ?? 0;
                        
                        // Performance = (Sum of Releases / Total Provision) * 100
                        $perf = $totalProvision > 0 ? ($actual / $totalProvision) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $subhead->subhead_code }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-medium">
                            {{ $subhead->description }} {{-- Ensuring narration is shown --}}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 font-semibold">
                            ₦{{ number_format($totalProvision, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-blue-700 font-bold">
                            ₦{{ number_format($actual, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <span class="text-xs font-bold {{ $perf > 100 ? 'text-red-600' : 'text-gray-600' }}">
                                    {{ number_format($perf, 1) }}%
                                </span>
                                <div class="w-12 bg-gray-200 rounded-full h-1 hidden lg:block">
                                    <div class="h-1 rounded-full {{ $perf > 100 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ min($perf, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center py-20 bg-gray-50">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-gray-500 font-bold">No performance data found for this category group.</p>
                            <p class="text-gray-400 text-sm">Try selecting a different expenditure category or fiscal quarter.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>