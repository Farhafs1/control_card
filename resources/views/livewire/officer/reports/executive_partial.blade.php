<div class="space-y-6 print:p-0">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($dataset as $row)
            @php
                $label = $row->label;
                $actual = (float)($row->total ?? 0);
                $perf  = (float)($row->perf ?? 0);
                $cardColor = str_contains(strtolower($label), 'revenue') ? 'border-blue-600' : 'border-green-700';
            @endphp
            <div class="bg-white border-t-4 {{ $cardColor }} rounded-xl shadow-md p-5">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $label }}</p>
                <h4 class="text-xl font-black text-gray-900">₦{{ number_format($actual, 0) }}</h4>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-[12px] font-bold text-gray-800">Perf: {{ number_format($perf, 1) }}%</span>
                    <div class="w-24 bg-gray-100 rounded-full h-1.5 ml-2">
                        <div class="bg-green-600 h-1.5 rounded-full" style="width: {{ min($perf, 100) }}%"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Portfolio Executive Table --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="w-full overflow-x-auto">
            <table class="w-full border-collapse min-w-[1100px]">
                <thead>
                    <tr class="bg-green-900 text-white">
                        <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wider">Budget Category</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">Total Provision</th>
                        @foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $q)
                            <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">{{ $q }} Actual</th>
                        @endforeach
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider bg-green-700">Annual Perf %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php 
                        $gProv = 0; $gAct = 0; 
                        $qTotals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
                    @endphp
                    @foreach($dataset as $row)
                        @php 
                            $total_prov = (float)($row->budget ?? 0);
                            $actual = (float)($row->total ?? 0);
                            $perf = (float)($row->perf ?? 0);
                            
                            $gProv += $total_prov; 
                            $gAct += $actual;
                            foreach($qTotals as $q => $val) { $qTotals[$q] += (float)($row->$q ?? 0); }
                        @endphp
                        
                        <tr class="hover:bg-green-50 transition-colors border-l border-gray-100">
                            <td class="px-4 py-4 text-base font-black text-gray-800">{{ $row->label }}</td>
                            <td class="px-4 py-4 text-right text-base font-bold text-gray-900">₦{{ number_format($total_prov, 2) }}</td>
                            
                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                @php $qVal = (float) ($row->$q ?? 0); @endphp
                                <td class="px-4 py-4 text-right border-l border-gray-100">
                                    <div class="text-base font-semibold text-gray-900">₦{{ number_format($qVal, 2) }}</div>
                                </td>
                            @endforeach

                            <td class="px-4 py-4 text-right bg-green-100 border-l border-green-200">
                                <span class="inline-block px-2 py-1 bg-green-800 text-white rounded text-sm font-black min-w-[45px] text-center">
                                    {{ number_format($perf, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-900 text-white">
                    <tr>
                        <td class="px-4 py-5 text-sm font-black uppercase tracking-widest">Grand Totals</td>
                        <td class="px-4 py-5 text-right text-base font-bold">₦{{ number_format($gProv, 2) }}</td>
                        @foreach($qTotals as $total)
                            <td class="px-4 py-5 text-right text-base font-bold text-green-400">₦{{ number_format($total, 2) }}</td>
                        @endforeach
                        <td class="px-4 py-5 text-right bg-green-700">
                            <div class="text-sm text-green-200 font-bold uppercase">State Perf</div>
                            <div class="text-base font-black">{{ $gProv > 0 ? number_format(($gAct / $gProv) * 100, 1) : '0.0' }}%</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>