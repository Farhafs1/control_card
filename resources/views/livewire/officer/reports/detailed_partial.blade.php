<div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-800 text-white uppercase text-xs font-bold">
                <th class="px-4 py-4 border-b">Code</th>
                <th class="px-4 py-4 border-b w-1/3">Description (Subhead)</th>
                <th class="px-4 py-4 border-b text-right">Provision</th>
                <th class="px-4 py-4 border-b text-right">Actual</th>
                <th class="px-4 py-4 border-b text-right">Balance</th> {{-- New Column --}}
                <th class="px-4 py-4 border-b text-center">Utilization (%)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($dataset as $mda)
                @php
                    $mdaTotalProv = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
                    $mdaTotalActual = $mda->subheads->sum('releases_sum_amount');
                    $mdaPerf = $mdaTotalProv > 0 ? ($mdaTotalActual / $mdaTotalProv) * 100 : 0;
                @endphp

                {{-- MDA DEMARCATION ROW --}}
                <tr class="bg-green-800">
                    <td colspan="6" class="px-4 py-3"> {{-- Changed colspan to 6 --}}
                        <div class="flex justify-between items-center">
                            <span class="text-white font-black text-sm tracking-widest uppercase">
                                <span class="ml-2 text-green-300 font-normal normal-case text-xs"> {{ $mda->mda_code }} -</span>
                                {{ $mda->name }}
                            </span>
                            <span class="text-xs font-bold px-3 py-1 rounded bg-green-900 {{ $mdaPerf > 25 ? 'text-green-300' : 'text-amber-300' }}">
                                Portfolio Performance: {{ number_format($mdaPerf, 1) }}%
                            </span>
                        </div>
                    </td>
                </tr>

                @foreach($mda->subheads as $subhead)
                    @php
                        $totalProvision = $subhead->approved_provision + $subhead->additional_provision;
                        $actual = $subhead->releases_sum_amount ?? 0;
                        $balance = $totalProvision - $actual; // Calculation
                        $perf = $totalProvision > 0 ? ($actual / $totalProvision) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $subhead->subhead_code }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-medium">{{ $subhead->description }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 font-semibold">
                            ₦{{ number_format($totalProvision, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-green-700 font-bold">
                            ₦{{ number_format($actual, 2) }}
                        </td>
                        {{-- New Balance Cell --}}
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $balance < 0 ? 'text-red-600' : 'text-blue-600' }}">
                            ₦{{ number_format($balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <span class="text-xs font-bold {{ $perf > 100 ? 'text-red-600' : 'text-gray-600' }}">
                                    {{ number_format($perf, 1) }}%
                                </span>
                                <div class="w-12 bg-gray-200 rounded-full h-1 hidden lg:block">
                                    <div class="h-1 rounded-full {{ $perf > 100 ? 'bg-red-500' : 'bg-green-600' }}" style="width: {{ min($perf, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center py-20 bg-gray-50">
                        <p class="text-gray-500 font-bold">No detailed data found for this category.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>