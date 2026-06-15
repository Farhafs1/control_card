<div class="overflow-x-auto bg-white rounded-xl shadow-md border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-green-700 text-white uppercase text-xs font-bold">
                <th class="px-4 py-4 border-b">Code</th>
                <th class="px-4 py-4 border-b w-1/3">Description (Subhead)</th>
                <th class="px-4 py-4 border-b text-right">Provision</th>
                <th class="px-4 py-4 border-b text-right">Actual</th>
                <th class="px-4 py-4 border-b text-right">Balance</th>
                <th class="px-4 py-4 border-b text-center">Utilization (%)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($results as $mda)
                @php
                    $mdaTotalProv = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
                    $mdaTotalActual = $mda->subheads->sum('releases_sum_amount');
                    $mdaBalance = $mdaTotalProv - $mdaTotalActual;
                    $mdaPerf = $mdaTotalProv > 0 ? ($mdaTotalActual / $mdaTotalProv) * 100 : 0;
                @endphp

                {{-- MDA Header --}}
                <tr class="bg-gray-800 text-white">
                    <td colspan="6" class="px-4 py-3 font-black text-sm tracking-widest uppercase">
                        <span class="text-gray-400 font-normal normal-case">{{ $mda->mda_code }} -</span> {{ $mda->name }}
                    </td>
                </tr>

                @foreach($mda->subheads as $subhead)
                    @php
                        $totalProvision = $subhead->approved_provision + $subhead->additional_provision;
                        $actual = $subhead->releases_sum_amount ?? 0;
                        $balance = $totalProvision - $actual;
                        $perf = $totalProvision > 0 ? ($actual / $totalProvision) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ $subhead->subhead_code }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-medium">{{ $subhead->description }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 font-semibold">₦{{ number_format($totalProvision, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-700 font-bold">₦{{ number_format($actual, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $balance < 0 ? 'text-red-600' : 'text-blue-600' }}">₦{{ number_format($balance, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-bold {{ $perf > 100 ? 'text-red-600' : 'text-gray-600' }}">{{ number_format($perf, 1) }}%</span>
                        </td>
                    </tr>
                @endforeach

                {{-- MDA Totals Footer Row --}}
                <tr class="bg-green-50 font-bold border-b-2 border-green-200">
                    <td colspan="2" class="px-4 py-3 text-right uppercase text-xs text-green-900">MDA Totals</td>
                    <td class="px-4 py-3 text-sm text-right text-green-900">₦{{ number_format($mdaTotalProv, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-900">₦{{ number_format($mdaTotalActual, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-900">₦{{ number_format($mdaBalance, 2) }}</td>
                    <td class="px-4 py-3 text-center text-sm text-green-900">{{ number_format($mdaPerf, 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-20 bg-gray-50 text-gray-500 font-bold">No performance data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>