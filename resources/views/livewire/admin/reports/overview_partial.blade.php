<div class="space-y-6 print:p-0">
    {{-- Quarterly Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($results as $row)
            @php
                $label = $row['label'] ?? $row['category_name'];
                // For Quarterly, we show the Actual for that specific quarter
                $actual = (float) ($row['actual'] ?? 0);
                $perf  = (float) ($row['perf'] ?? 0);
                
                $cardColor = str_contains(strtolower($label), 'revenue') ? 'border-blue-600' : 'border-green-700';
                $barColor = str_contains(strtolower($label), 'revenue') ? 'bg-blue-600' : 'bg-green-600';
            @endphp
            <div class="bg-white border-t-4 {{ $cardColor }} rounded-xl shadow-md p-5 transition-transform hover:scale-105">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $label }}</p>
                <h4 class="text-xl font-black text-gray-900">₦{{ number_format($actual, 0) }}</h4>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-[11px] font-bold text-gray-500">Q{{ $quarter }} Perf: {{ number_format($perf, 1) }}%</span>
                    <div class="w-24 bg-gray-100 rounded-full h-1.5 ml-2">
                        <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ min($perf, 100) }}%"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quarterly Table Container --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="w-full overflow-x-auto">
            <table class="w-full border-collapse min-w-[1100px]">
                <thead>
                    <tr class="bg-green-900 text-white">
                        <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wider">Budget Category</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">Original Approved</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">Additional</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">Total Provision</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider bg-green-800">Actual Q{{ $quarter }}</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider">Balance</th>
                        <th class="px-4 py-4 text-right text-xs font-black uppercase tracking-wider bg-green-700">Perf %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php 
                        $gApp = 0; $gAdd = 0; $gProv = 0; $gAct = 0; 
                    @endphp
                    
                    @foreach($results as $row)
                        @php 
                            $app   = (float) ($row['approved'] ?? 0);
                            $add   = (float) ($row['additional'] ?? 0);
                            $total_prov = (float) ($row['total_prov'] ?? ($app + $add));
                            $actual = (float) ($row['actual'] ?? 0);
                            $perf   = (float) ($row['perf'] ?? 0);
                            $balance = (float) ($row['balance'] ?? ($total_prov - $actual));

                            $gApp += $app; $gAdd += $add; $gProv += $total_prov; $gAct += $actual;
                        @endphp
                        
                        <tr class="hover:bg-green-50 transition-colors">
                            <td class="px-4 py-4 text-sm font-black text-gray-800">{{ $row['label'] }}</td>
                            <td class="px-4 py-4 text-right text-sm font-mono text-gray-600">₦{{ number_format($app, 2) }}</td>
                            <td class="px-4 py-4 text-right text-sm font-mono text-blue-600">₦{{ number_format($add, 2) }}</td>
                            <td class="px-4 py-4 text-right text-sm font-mono font-bold">₦{{ number_format($total_prov, 2) }}</td>
                            <td class="px-4 py-4 text-right text-sm font-mono text-green-700 font-bold bg-green-50">
                                ₦{{ number_format($actual, 2) }}
                            </td>
                            <td class="px-4 py-4 text-right text-sm font-mono {{ $balance < 0 ? 'text-red-600' : 'text-gray-500' }}">
                                ₦{{ number_format($balance, 2) }}
                            </td>
                            <td class="px-4 py-4 text-right bg-green-100 border-l border-green-200">
                                <span class="inline-block px-2 py-1 bg-green-800 text-white rounded text-[10px] font-black min-w-[45px] text-center">
                                    {{ number_format($perf, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-900 text-white">
                    <tr>
                        <td class="px-4 py-5 text-xs font-black uppercase tracking-widest">Grand Totals</td>
                        <td class="px-4 py-5 text-right font-mono text-xs">₦{{ number_format($gApp, 2) }}</td>
                        <td class="px-4 py-5 text-right font-mono text-xs">₦{{ number_format($gAdd, 2) }}</td>
                        <td class="px-4 py-5 text-right font-mono text-sm font-bold">₦{{ number_format($gProv, 2) }}</td>
                        <td class="px-4 py-5 text-right font-mono text-sm text-green-400 font-bold">₦{{ number_format($gAct, 2) }}</td>
                        <td class="px-4 py-5 text-right font-mono text-sm text-gray-400">₦{{ number_format($gProv - $gAct, 2) }}</td>
                        <td class="px-4 py-5 text-right bg-green-700">
                            <div class="text-[10px] text-green-200 font-bold uppercase">State Perf</div>
                            <div class="text-base font-black">{{ $gProv > 0 ? number_format(($gAct / $gProv) * 100, 1) : '0.0' }}%</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>